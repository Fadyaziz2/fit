import 'package:flutter/material.dart';
import 'package:mighty_fitness/components/exercise_component.dart';
import 'package:mighty_fitness/components/manual_workout_history_table.dart';
import 'package:mighty_fitness/extensions/animatedList/animated_list_view.dart';
import 'package:mighty_fitness/extensions/decorations.dart';
import 'package:mighty_fitness/extensions/extension_util/int_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/string_extensions.dart';
import 'package:mighty_fitness/extensions/loader_widget.dart';
import 'package:mighty_fitness/main.dart';
import 'package:mighty_fitness/models/exercise_response.dart';
import 'package:mighty_fitness/models/manual_exercise_response.dart';
import 'package:mighty_fitness/network/rest_api.dart';
import 'package:mighty_fitness/screens/no_data_screen.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_common.dart';
import 'package:mighty_fitness/extensions/text_styles.dart';
import '../../extensions/widgets.dart';

class WorkoutHistoryScreen extends StatefulWidget {
  static String tag = '/WorkoutHistoryScreen';

  @override
  WorkoutHistoryScreenState createState() => WorkoutHistoryScreenState();
}

class WorkoutHistoryScreenState extends State<WorkoutHistoryScreen> {

  List<ExerciseModel> mExerciseList = [];
  List<ManualExerciseModel> manualExercises = [];
  ScrollController scrollController = ScrollController();

  int page = 1;
  int? numPage;

  bool isLastPage = false;
  bool isManualLoading = false;

  final List<String> manualSports = [
    'مشى',
    'مشى سريع',
    'هروله',
    'جرى',
    'نط حبل',
    'صعود درج',
    'عجله',
    'كره سله',
    'كره قدم',
    'سباحه',
    'تنس',
    'رفع اثقال',
    'اخرى',
  ];
  @override
  void initState() {
        super.initState();
        init();
        scrollController.addListener(() {
          if (scrollController.position.pixels == scrollController.position.maxScrollExtent && !appStore.isLoading) {
            if (numPage != null && page < numPage!) {
              page++;
              init();
            }
          }
        });
  }

  void init() async {
    getExerciseData();
    _fetchManualExercises();
  }

  Future<void> getExerciseData() async {
    appStore.setLoading(true);
    await getExerciseListApi(page: page).then((value) {
      appStore.setLoading(false);
      numPage = value.pagination!.totalPages;
      isLastPage = false;
      if (page == 1) {
        mExerciseList.clear();
      }
      Iterable it = value.data!;
      it.map((e) => mExerciseList.add(e)).toList();
      setState(() {});
    }).catchError((e) {
      isLastPage = true;
      appStore.setLoading(false);
      setState(() {});
    });
  }

  Future<void> _fetchManualExercises() async {
    if (!mounted) return;
    setState(() {
      isManualLoading = true;
    });
    try {
      final response = await getManualExerciseListApi(page: 1);
      manualExercises = response.data ?? [];
    } catch (e) {
      toast(e.toString());
    } finally {
      if (mounted) {
        setState(() {
          isManualLoading = false;
        });
      }
    }
  }

  Widget _buildManualPlaceholder(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      padding: const EdgeInsets.all(16),
      decoration: boxDecorationWithRoundedCorners(
        borderRadius: radius(16),
        backgroundColor: appStore.isDarkMode ? context.cardColor : Colors.white,
        border: Border.all(color: context.dividerColor.withOpacity(0.2)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.directions_run, color: primaryColor),
          12.width,
          Expanded(
            child: Text(
              appStore.selectedLanguageCode == 'ar'
                  ? 'لم تقم بإضافة أي نشاط يدوي بعد. استخدم زر الإضافة أعلاه.'
                  : 'No manual workouts yet. Use the add button above to log one.',
              style: secondaryTextStyle(),
            ),
          ),
        ],
      ),
    );
  }

  void _showAddManualExerciseSheet() {
    String selectedSport = manualSports.first;
    final TextEditingController durationController = TextEditingController();
    final TextEditingController customSportController = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final bool isOther = selectedSport == 'اخرى';
            return Padding(
              padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(16, 20, 16, 16),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      appStore.selectedLanguageCode == 'ar'
                          ? 'إضافة نشاط يدوي'
                          : 'Add Manual Workout',
                      style: boldTextStyle(size: 18),
                    ),
                    16.height,
                    DropdownButtonFormField<String>(
                      value: selectedSport,
                      decoration: InputDecoration(
                        labelText: appStore.selectedLanguageCode == 'ar' ? 'الرياضة' : 'Sport',
                        border: OutlineInputBorder(borderRadius: radius(12)),
                      ),
                      items: manualSports
                          .map(
                            (sport) => DropdownMenuItem<String>(
                              value: sport,
                              child: Text(sport),
                            ),
                          )
                          .toList(),
                      onChanged: (value) {
                        if (value == null) return;
                        setModalState(() {
                          selectedSport = value;
                        });
                      },
                    ),
                    16.height,
                    if (isOther)
                      TextField(
                        controller: customSportController,
                        decoration: InputDecoration(
                          labelText: appStore.selectedLanguageCode == 'ar'
                              ? 'اسم الرياضة'
                              : 'Sport name',
                          border: OutlineInputBorder(borderRadius: radius(12)),
                        ),
                      ),
                    if (isOther) 16.height,
                    TextField(
                      controller: durationController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: appStore.selectedLanguageCode == 'ar'
                            ? 'المدة بالدقائق'
                            : 'Duration (minutes)',
                        border: OutlineInputBorder(borderRadius: radius(12)),
                      ),
                    ),
                    24.height,
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () async {
                          final bool isOtherSelected = selectedSport == 'اخرى';
                          final String customValue = customSportController.text.trim();
                          if (isOtherSelected && customValue.isEmpty) {
                            toast(appStore.selectedLanguageCode == 'ar'
                                ? 'يرجى إدخال اسم الرياضة.'
                                : 'Please enter the sport name.');
                            return;
                          }

                          final String durationText = durationController.text.trim();
                          final double? duration = double.tryParse(durationText);
                          if (duration == null || duration <= 0) {
                            toast(appStore.selectedLanguageCode == 'ar'
                                ? 'يرجى إدخال مدة صحيحة.'
                                : 'Please enter a valid duration.');
                            return;
                          }

                          FocusScope.of(context).unfocus();
                          appStore.setLoading(true);
                          Map<String, dynamic> req = {
                            'sport': isOtherSelected ? 'other' : selectedSport,
                            'custom_sport': isOtherSelected ? customValue : '',
                            'duration': duration,
                          };

                          await storeManualExercise(req).then((value) {
                            toast(value.message.validate());
                            Navigator.of(context).pop();
                            _fetchManualExercises();
                          }).catchError((e) {
                            toast(e.toString());
                          }).whenComplete(() {
                            appStore.setLoading(false);
                          });
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: primaryColor,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: radius(12)),
                        ),
                        child: Text(
                          appStore.selectedLanguageCode == 'ar' ? 'حفظ' : languages.lblSave,
                          style: boldTextStyle(color: Colors.white),
                        ),
                      ),
                    ),
                    8.height,
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }


  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: appBarWidget(
        appStore.selectedLanguageCode == 'ar' ? 'سجل التمارين' : 'Workout History',
        context: context,
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            tooltip: appStore.selectedLanguageCode == 'ar'
                ? 'إضافة تمرين يدوي'
                : 'Add Manual Workout',
            onPressed: _showAddManualExerciseSheet,
          ),
        ],
      ),
      body: Column(
        children: [
          if (isManualLoading)
            const SizedBox(
              height: 2,
              child: LinearProgressIndicator(),
            ),
          if (!isManualLoading && manualExercises.isEmpty)
            _buildManualPlaceholder(context),
          ManualWorkoutHistoryTable(
            entries: manualExercises,
            title: appStore.selectedLanguageCode == 'ar'
                ? 'سجل التمارين اليدوي'
                : 'Manual Workout History',
          ),
          12.height,
          Expanded(
            child: Stack(
              children: [
                mExerciseList.isNotEmpty
                    ? AnimatedListView(
                        controller: scrollController,
                        shrinkWrap: true,
                        padding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                        itemCount: mExerciseList.length,
                        itemBuilder: (context, index) {
                          return ExerciseComponent(mExerciseModel: mExerciseList[index]);
                        },
                      )
                    : NoDataScreen(mTitle: languages.lblExerciseNoFound).visible(!appStore.isLoading),
                Loader().center().visible(appStore.isLoading),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
