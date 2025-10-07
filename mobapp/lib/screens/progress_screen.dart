import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_mobx/flutter_mobx.dart';
import 'package:intl/intl.dart';
import 'package:file_picker/file_picker.dart';
import 'package:cached_network_image/cached_network_image.dart';

import '../components/bmr_component.dart';
import '../components/ideal_weight_component.dart';
import '../extensions/extension_util/list_extensions.dart';
import '../extensions/LiveStream.dart';
import '../extensions/shared_pref.dart';
import '../main.dart';
import '../../components/bmi_component.dart';
import '../../components/step_count_component.dart';
import '../../extensions/extension_util/context_extensions.dart';
import '../../extensions/extension_util/int_extensions.dart';
import '../../extensions/extension_util/widget_extensions.dart';
import '../../extensions/extension_util/string_extensions.dart';
import '../extensions/app_button.dart';
import '../extensions/app_text_field.dart';
import '../extensions/common.dart';
import '../extensions/constants.dart';
import '../../screens/progress_detail_screen.dart';
import '../components/horizontal_bar_chart.dart';
import '../extensions/decorations.dart';
import '../extensions/text_styles.dart';
import '../extensions/widgets.dart';
import '../network/rest_api.dart';
import '../extensions/colors.dart';
import '../utils/app_colors.dart';
import '../utils/app_constants.dart';
import '../models/diet_response.dart';
import '../models/ingredient_model.dart';
import '../models/health_condition_model.dart';
import '../models/user_attachment.dart';
import '../utils/app_common.dart';

class ProgressScreen extends StatefulWidget {
  static String tag = '/ProgressScreen';

  @override
  ProgressScreenState createState() => ProgressScreenState();
}

class ProgressScreenState extends State<ProgressScreen> {
  bool? isWeight, isHeartRate, isPush;
  bool _isLoadingSuggestions = false;
  bool _isUpdatingHealth = false;
  bool _isUploadingAttachments = false;
  bool _isLoadingIngredients = false;
  List<DietModel> _suggestedMeals = [];
  List<IngredientModel> _availableIngredients = [];
  final DateFormat _dateFormatter = DateFormat('yyyy-MM-dd');

  @override
  void initState() {
    super.initState();
    init();
    LiveStream().emit(IdealWeight);
    getDoubleAsync(IdealWeight);

    LiveStream().on(PROGRESS_SETTING, (p0) {
      userStore.mProgressList.forEachIndexed((element, index) {
        if (element.id == 1) {
          isWeight = element.isEnable;
        }
        if (element.id == 2) {
          isHeartRate = element.isEnable;
        }
        if (element.id == 3) {
          isPush = element.isEnable;
        }
        setState(() {});
      });
    });
  }

  init() async {
    userStore.mProgressList.forEachIndexed((element, index) {
      if (element.id == 1) {
        isWeight = element.isEnable;
        if (element.isEnable == true) {
          getProgressApi(METRICS_WEIGHT);
        }
      }
      if (element.id == 2) {
        isHeartRate = element.isEnable;
        if (element.isEnable == true) {
          getProgressApi(METRICS_HEART_RATE);
        }
      }
      if (element.id == 3) {
        isPush = element.isEnable;
        if (element.isEnable == true) {
          getProgressApi(PUSH_UP_MIN_UNIT);
        }
      }
    });
    setState(() {});

    await _refreshUserData();
    await _fetchSuggestedMeals();
  }

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
  }

  Widget mHeading(String? value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(value!, style: boldTextStyle()),
        8.width,
        Icon(Icons.keyboard_arrow_right, color: primaryColor),
      ],
    ).paddingSymmetric(horizontal: 16, vertical: 8);
  }

  Future<void> _refreshUserData() async {
    if (!userStore.isLoggedIn) return;
    await getUSerDetail(context, userStore.userId).catchError((e) {
      toast(e.toString());
    });
  }

  Future<void> _fetchSuggestedMeals() async {
    if (!userStore.isLoggedIn) return;
    setState(() {
      _isLoadingSuggestions = true;
    });

    await getDietApi(null, false, page: 1).then((response) {
      final meals = response.data ?? [];
      _suggestedMeals = meals.where((meal) => meal.isFavourite != 1).take(6).toList();
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isLoadingSuggestions = false;
        });
      }
    });
  }

  Future<void> _ensureIngredientsLoaded() async {
    if (_availableIngredients.isNotEmpty || _isLoadingIngredients) return;

    setState(() {
      _isLoadingIngredients = true;
    });

    await getIngredientListApi().then((response) {
      _availableIngredients = response.data ?? [];
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isLoadingIngredients = false;
        });
      }
    });
  }

  Future<void> _updateHealthProfile({
    List<IngredientModel>? disliked,
    List<HealthConditionModel>? conditions,
    String? notes,
  }) async {
    if (_isUpdatingHealth) return;

    final dislikedItems = disliked ?? userStore.dislikedIngredients.toList();
    final conditionItems = conditions ?? userStore.healthConditions.toList();
    final notesValue = notes ?? userStore.healthNotes;

    setState(() {
      _isUpdatingHealth = true;
    });

    final Map<String, dynamic> payload = {
      'disliked_ingredients':
          dislikedItems.map((e) => e.id).whereType<int>().toList(),
      'diseases': conditionItems
          .where((element) => element.name.validate().isNotEmpty && element.startedAt.validate().isNotEmpty)
          .map((e) => {
                'name': e.name,
                'started_at': e.startedAt,
              })
          .toList(),
      'notes': notesValue,
    };

    if (userStore.assignedSpecialistId != null) {
      payload['specialist_id'] = userStore.assignedSpecialistId;
    }

    await updateHealthProfileApi(payload).then((response) async {
      await userStore.setDislikedIngredients(response.data?.dislikedIngredients ?? []);
      await userStore.setHealthConditions(response.data?.healthConditions ?? []);
      await userStore.setAttachments(response.data?.attachments ?? []);
      await userStore.setHealthNotes(response.data?.userProfile?.notes.validate() ?? '');
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isUpdatingHealth = false;
        });
      }
    });
  }

  Future<void> _showIngredientSelector() async {
    await _ensureIngredientsLoaded();
    if (!mounted) return;

    final currentSelection = userStore.dislikedIngredients.map((e) => e.id).whereType<int>().toSet();

    final result = await showModalBottomSheet<List<int>>(
      context: context,
      isScrollControlled: true,
      builder: (ctx) {
        final tempSelection = currentSelection.toSet();
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: SafeArea(
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(languages.lblDislikedMeals, style: boldTextStyle(size: 18)),
                          IconButton(
                            icon: Icon(Icons.close),
                            onPressed: () => Navigator.pop(context),
                          )
                        ],
                      ),
                      8.height,
                      if (_isLoadingIngredients)
                        Center(child: CircularProgressIndicator())
                            .paddingSymmetric(vertical: 32)
                      else if (_availableIngredients.isEmpty)
                        Text(languages.lblNoFoundData, style: secondaryTextStyle()).center().paddingSymmetric(vertical: 32)
                      else
                        SizedBox(
                          height: context.height() * 0.5,
                          child: ListView.builder(
                            itemCount: _availableIngredients.length,
                            itemBuilder: (_, index) {
                              final ingredient = _availableIngredients[index];
                              final isSelected = tempSelection.contains(ingredient.id);
                              return CheckboxListTile(
                                value: isSelected,
                                onChanged: (value) {
                                  setModalState(() {
                                    if (value == true) {
                                      if (ingredient.id != null) tempSelection.add(ingredient.id!);
                                    } else {
                                      if (ingredient.id != null) tempSelection.remove(ingredient.id);
                                    }
                                  });
                                },
                                title: Text(ingredient.title.validate()),
                              );
                            },
                          ),
                        ),
                      12.height,
                      AppButton(
                        text: languages.lblSave,
                        width: context.width(),
                        onTap: () {
                          Navigator.pop(context, tempSelection.toList());
                        },
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );

    if (result != null) {
      final updated = _availableIngredients
          .where((element) => result.contains(element.id))
          .toList();
      await _updateHealthProfile(disliked: updated);
    }
  }

  Future<void> _showConditionDialog({HealthConditionModel? condition}) async {
    final nameController = TextEditingController(text: condition?.name ?? '');
    DateTime? selectedDate = condition?.startedAt != null
        ? DateTime.tryParse(condition!.startedAt!)
        : null;

    final result = await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
              child: SafeArea(
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(languages.lblAddCondition, style: boldTextStyle(size: 18)),
                      12.height,
                      AppTextField(
                        controller: nameController,
                        decoration: defaultInputDecoration(context, label: languages.lblConditionName),
                        textFieldType: TextFieldType.NAME,
                      ),
                      12.height,
                      AppButton(
                        text: selectedDate != null
                            ? DateFormat.yMMMd(appStore.selectedLanguageCode).format(selectedDate!)
                            : languages.lblConditionStartDate,
                        color: context.cardColor,
                        textStyle: primaryTextStyle(color: textPrimaryColorGlobal),
                        onTap: () async {
                          final now = DateTime.now();
                          final picked = await showDatePicker(
                            context: context,
                            initialDate: selectedDate ?? now,
                            firstDate: DateTime(now.year - 80),
                            lastDate: now,
                          );
                          if (picked != null) {
                            setModalState(() {
                              selectedDate = picked;
                            });
                          }
                        },
                      ),
                      16.height,
                      AppButton(
                        text: languages.lblSave,
                        width: context.width(),
                        onTap: () {
                          Navigator.pop(context, {
                            'name': nameController.text.trim(),
                            'date': selectedDate,
                          });
                        },
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );

    if (result != null) {
      final name = result['name'] as String?;
      final date = result['date'] as DateTime?;

      if (name.validate().isEmpty || date == null) {
        toast(languages.lblConditionName);
        return;
      }

      final conditions = userStore.healthConditions.toList();
      if (condition != null) {
        conditions.removeWhere((element) => element == condition);
      }
      conditions.add(HealthConditionModel(
        name: name,
        startedAt: _dateFormatter.format(date),
        startedAtFormatted: DateFormat.yMMMd(appStore.selectedLanguageCode).format(date),
      ));

      await _updateHealthProfile(conditions: conditions);
    }
  }

  Future<void> _removeCondition(HealthConditionModel condition) async {
    final updated = userStore.healthConditions
        .where((element) => element != condition)
        .toList();
    await _updateHealthProfile(conditions: updated);
  }

  Future<void> _removeDislikedIngredient(IngredientModel ingredient) async {
    final updated = userStore.dislikedIngredients
        .where((element) => element.id != ingredient.id)
        .toList();
    await _updateHealthProfile(disliked: updated);
  }

  Future<void> _pickAttachments() async {
    if (_isUploadingAttachments) return;

    final result = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
    );

    final files = result?.paths.whereType<String>().map((e) => File(e)).toList() ?? [];
    if (files.isEmpty) return;

    setState(() {
      _isUploadingAttachments = true;
    });

    await uploadUserAttachmentsApi(files).then((value) async {
      await userStore.setAttachments(value);
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isUploadingAttachments = false;
        });
      }
    });
  }

  Future<void> _deleteAttachment(UserAttachment attachment) async {
    if (attachment.id == null) return;
    setState(() {
      _isUploadingAttachments = true;
    });

    await deleteUserAttachmentApi(attachment.id!).then((value) async {
      await userStore.setAttachments(value);
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isUploadingAttachments = false;
        });
      }
    });
  }

  Future<void> _toggleFavouriteMeal(DietModel meal) async {
    if (meal.id == null) return;

    Map req = {'diet_id': meal.id};
    await setDietFavApi(req).then((value) {
      meal.isFavourite = meal.isFavourite == 1 ? 0 : 1;
      _fetchSuggestedMeals();
    }).catchError((e) {
      toast(e.toString());
    });
  }

  Widget _buildHealthConditionsSection(BuildContext context) {
    return Observer(builder: (_) {
      final conditions = userStore.healthConditions.toList();
      return Container(
        margin: EdgeInsets.symmetric(horizontal: 16),
        padding: EdgeInsets.all(16),
        decoration: boxDecorationRoundedWithShadow(16, backgroundColor: context.cardColor),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(languages.lblHealthConditions, style: boldTextStyle()),
                _isUpdatingHealth
                    ? SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                    : IconButton(
                        icon: Icon(Icons.add),
                        onPressed: () => _showConditionDialog(),
                      ),
              ],
            ),
            8.height,
            if (conditions.isEmpty)
              Text(languages.lblNoFoundData, style: secondaryTextStyle())
            else
              Column(
                children: conditions.map((condition) {
                  return Column(
                    children: [
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(condition.name.validate(), style: primaryTextStyle()),
                        subtitle: condition.startedAtFormatted.validate().isNotEmpty
                            ? Text(condition.startedAtFormatted.validate(), style: secondaryTextStyle())
                            : null,
                        trailing: IconButton(
                          icon: Icon(Icons.delete_outline, color: redColor),
                          onPressed: () => _removeCondition(condition),
                        ),
                        onTap: () => _showConditionDialog(condition: condition),
                      ),
                      if (conditions.last != condition) Divider(height: 0),
                    ],
                  );
                }).toList(),
              ),
          ],
        ),
      );
    });
  }

  Widget _buildDislikedMealsSection(BuildContext context) {
    return Observer(builder: (_) {
      final disliked = userStore.dislikedIngredients.toList();
      return Container(
        margin: EdgeInsets.symmetric(horizontal: 16),
        padding: EdgeInsets.all(16),
        decoration: boxDecorationRoundedWithShadow(16, backgroundColor: context.cardColor),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(languages.lblDislikedMeals, style: boldTextStyle()),
                Row(
                  children: [
                    if (_isUpdatingHealth)
                      SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)).paddingRight(8),
                    TextButton(onPressed: _showIngredientSelector, child: Text(languages.lblManage)),
                  ],
                ),
              ],
            ),
            8.height,
            if (disliked.isEmpty)
              Text(languages.lblNoFoundData, style: secondaryTextStyle())
            else
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: disliked.map((item) {
                  return Chip(
                    label: Text(item.title.validate()),
                    onDeleted: () => _removeDislikedIngredient(item),
                  );
                }).toList(),
              ),
          ],
        ),
      );
    });
  }

  Widget _buildAttachmentsSection(BuildContext context) {
    return Observer(builder: (_) {
      final items = userStore.attachments.toList();
      return Container(
        margin: EdgeInsets.symmetric(horizontal: 16),
        padding: EdgeInsets.all(16),
        decoration: boxDecorationRoundedWithShadow(16, backgroundColor: context.cardColor),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(languages.lblAttachments, style: boldTextStyle()),
                TextButton.icon(
                  onPressed: _pickAttachments,
                  icon: _isUploadingAttachments
                      ? SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                      : Icon(Icons.upload_file),
                  label: Text(languages.lblUploadDocument),
                ),
              ],
            ),
            8.height,
            if (items.isEmpty && !_isUploadingAttachments)
              Text(languages.lblNoFoundData, style: secondaryTextStyle())
            else
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: items.map((attachment) {
                  final isImage = attachment.mimeType.validate().startsWith('image');
                  final preview = isImage
                      ? CachedNetworkImage(imageUrl: attachment.url.validate(), fit: BoxFit.cover)
                      : Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.insert_drive_file, size: 32, color: textSecondaryColorGlobal),
                            4.height,
                            Text(attachment.name.validate(), style: secondaryTextStyle(), maxLines: 1, overflow: TextOverflow.ellipsis),
                          ],
                        );

                  return GestureDetector(
                    onTap: () {
                      launchUrls(attachment.url.validate());
                    },
                    child: Stack(
                      children: [
                        Container(
                          width: 110,
                          height: 110,
                          decoration: boxDecorationWithRoundedCorners(
                            borderRadius: radius(12),
                            backgroundColor: context.cardColor,
                          ),
                          clipBehavior: Clip.antiAlias,
                          child: isImage
                              ? preview
                              : Container(
                                  padding: EdgeInsets.all(12),
                                  alignment: Alignment.center,
                                  child: preview,
                                ),
                        ),
                        Positioned(
                          right: 4,
                          top: 4,
                          child: InkWell(
                            onTap: () => _deleteAttachment(attachment),
                            child: Container(
                              decoration: boxDecorationWithRoundedCorners(
                                borderRadius: radius(12),
                                backgroundColor: context.scaffoldBackgroundColor,
                              ),
                              padding: EdgeInsets.all(4),
                              child: Icon(Icons.close, size: 16),
                            ),
                          ),
                        )
                      ],
                    ),
                  );
                }).toList(),
              ),
          ],
        ),
      );
    });
  }

  Widget _buildSuggestedMealsSection(BuildContext context) {
    if (!userStore.isLoggedIn) return SizedBox();

    return Container(
      margin: EdgeInsets.symmetric(horizontal: 16),
      padding: EdgeInsets.all(16),
      decoration: boxDecorationRoundedWithShadow(16, backgroundColor: context.cardColor),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(languages.lblSuggestedMeals, style: boldTextStyle()),
              if (_isLoadingSuggestions)
                SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
            ],
          ),
          8.height,
          if (_isLoadingSuggestions)
            Center(child: CircularProgressIndicator()).paddingSymmetric(vertical: 32)
          else if (_suggestedMeals.isEmpty)
            Text(languages.lblNoFoundData, style: secondaryTextStyle())
          else
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: _suggestedMeals.map((meal) {
                  return Container(
                    width: 200,
                    margin: EdgeInsets.only(right: 12),
                    decoration: boxDecorationWithRoundedCorners(
                      borderRadius: radius(16),
                      backgroundColor: context.cardColor,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
                          child: Image.network(
                            meal.dietImage.validate(),
                            height: 110,
                            width: 200,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => Container(
                              height: 110,
                              color: context.scaffoldBackgroundColor,
                              child: Icon(Icons.restaurant, size: 40, color: textSecondaryColorGlobal),
                            ),
                          ),
                        ),
                        Padding(
                          padding: EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(meal.title.validate(), style: boldTextStyle(), maxLines: 1, overflow: TextOverflow.ellipsis),
                              4.height,
                              Text('${meal.calories.validate()} ${languages.lblCalories}', style: secondaryTextStyle(size: 12)),
                              8.height,
                              AppButton(
                                text: meal.isFavourite == 1 ? languages.lblFavourite : languages.lblAdd,
                                width: 160,
                                onTap: () => _toggleFavouriteMeal(meal),
                                color: meal.isFavourite == 1 ? context.cardColor : primaryColor,
                                textColor: meal.isFavourite == 1 ? primaryColor : Colors.white,
                                padding: EdgeInsets.symmetric(vertical: 8),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Observer(builder: (context) {
      return Scaffold(
        appBar: appBarWidget(languages.lblReport, showBack: false, color: appStore.isDarkMode ? scaffoldColorDark : Colors.white, context: context, titleSpacing: 16),
        body: Observer(builder: (context) {
          return SingleChildScrollView(
            physics: BouncingScrollPhysics(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    StepCountComponent().expand(),
                    16.width,
                    BMIComponent().expand().visible(userStore.weightUnit.isNotEmpty && userStore.heightUnit.isNotEmpty),
                  ],
                ).paddingSymmetric(horizontal: 16),
                16.height,
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    BMRComponent().expand().visible(userStore.weightUnit.isNotEmpty && userStore.heightUnit.isNotEmpty),
                    16.width,
                    IdealWeightComponent().expand().visible(userStore.gender.isNotEmpty && userStore.heightUnit.isNotEmpty),
                  ],
                ).paddingSymmetric(horizontal: 16),
                16.height,
                _buildHealthConditionsSection(context),
                16.height,
                _buildDislikedMealsSection(context),
                16.height,
                _buildAttachmentsSection(context),
                16.height,
                _buildSuggestedMealsSection(context),
                16.height,
                if (isWeight == true)
                  FutureBuilder(
                    future: getProgressApi(METRICS_WEIGHT),
                    builder: (context, snapshot) {
                      if (snapshot.hasData)
                        return Container(
                          margin: EdgeInsets.symmetric(horizontal: 16),
                          decoration: appStore.isDarkMode
                              ? boxDecorationWithRoundedCorners(borderRadius: radius(16), backgroundColor: context.cardColor)
                              : boxDecorationRoundedWithShadow(16, backgroundColor: context.cardColor),
                          padding: EdgeInsets.symmetric(vertical: 8),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              mHeading(languages.lblWeight),
                              SizedBox(
                                child: SingleChildScrollView(
                                  primary: true,
                                  scrollDirection: Axis.horizontal,
                                  child: HorizontalBarChart(snapshot.data?.data).withSize(width: context.width(), height: 250),
                                ).paddingSymmetric(horizontal: 8),
                              )
                            ],
                          ).onTap(() async {
                            bool? res = await ProgressDetailScreen(mType: METRICS_WEIGHT, mUnit: METRICS_WEIGHT_UNIT, mTitle: languages.lblWeight).launch(context);
                            if (res == true) {
                              setState(() {});
                            }
                          }),
                        );
                      return snapWidgetHelper(snapshot, loadingWidget: SizedBox());
                    },
                  ).visible(userStore.weight.isNotEmpty && userStore.weightUnit.isNotEmpty),
                if (isHeartRate == true)
                  FutureBuilder(
                    future: getProgressApi(METRICS_HEART_RATE),
                    builder: (context, snapshot) {
                      if (snapshot.hasData)
                        return Container(
                          margin: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                          decoration: appStore.isDarkMode
                              ? boxDecorationWithRoundedCorners(borderRadius: radius(16), backgroundColor: context.cardColor)
                              : boxDecorationRoundedWithShadow(16, backgroundColor: context.cardColor),
                          padding: EdgeInsets.symmetric(vertical: 8),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              mHeading(languages.lblHeartRate),
                              SingleChildScrollView(
                                primary: true,
                                scrollDirection: Axis.horizontal,
                                child: HorizontalBarChart(snapshot.data!.data).withSize(width: context.width(), height: 250),
                              ).paddingSymmetric(horizontal: 8)
                            ],
                          ).onTap(() async {
                            bool? res = await ProgressDetailScreen(mType: METRICS_HEART_RATE, mUnit: METRICS_HEART_UNIT, mTitle: languages.lblHeartRate).launch(context);
                            if (res == true) {
                              setState(() {});
                            }
                          }),
                        );
                      return snapWidgetHelper(snapshot, loadingWidget: SizedBox());
                    },
                  ),
                if (isPush == true)
                  FutureBuilder(
                    future: getProgressApi(PUSH_UP_MIN),
                    builder: (context, snapshot) {
                      if (snapshot.hasData)
                        return Container(
                          margin: EdgeInsets.symmetric(horizontal: 16),
                          decoration: appStore.isDarkMode
                              ? boxDecorationWithRoundedCorners(borderRadius: radius(16), backgroundColor: context.cardColor)
                              : boxDecorationRoundedWithShadow(16, backgroundColor: context.cardColor),
                          padding: EdgeInsets.symmetric(vertical: 8),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              mHeading(languages.lblPushUp),
                              SingleChildScrollView(
                                primary: true,
                                scrollDirection: Axis.horizontal,
                                child: HorizontalBarChart(snapshot.data!.data).withSize(width: context.width(), height: 250),
                              ).paddingSymmetric(horizontal: 8)
                            ],
                          ).onTap(() async {
                            bool? res = await ProgressDetailScreen(mType: PUSH_UP_MIN, mUnit: PUSH_UP_MIN_UNIT, mTitle: languages.lblPushUp).launch(context);
                            if (res == true) {
                              setState(() {});
                            }
                          }),
                        );
                      return snapWidgetHelper(snapshot);
                    },
                  ),
                16.height,
              ],
            ),
          );
        }),
      );
    });
  }
}
