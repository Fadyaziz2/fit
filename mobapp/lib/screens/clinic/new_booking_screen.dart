import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:mighty_fitness/extensions/loader_widget.dart';
import 'package:mighty_fitness/extensions/text_styles.dart';
import 'package:mighty_fitness/main.dart';
import 'package:mighty_fitness/models/clinic_models.dart';
import 'package:mighty_fitness/network/rest_api.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_common.dart';

class NewBookingScreen extends StatefulWidget {
  const NewBookingScreen({Key? key}) : super(key: key);

  @override
  State<NewBookingScreen> createState() => _NewBookingScreenState();
}

class _NewBookingScreenState extends State<NewBookingScreen> {
  DateTime selectedDate = DateTime.now();
  List<ClinicSlot> slots = [];
  bool isLoadingSlots = true;
  String? selectedSlot;
  List<ClinicBranch> branches = [];
  int? selectedBranchId;
  bool isSubmitting = false;

  @override
  void initState() {
    super.initState();
    if ((userStore.assignedSpecialistBranchId ?? 0) == 0) {
      fetchClinicBranches().then((value) {
        setState(() {
          branches = value;
          if (branches.isNotEmpty) {
            selectedBranchId = branches.first.id;
          }
        });
      });
    } else {
      selectedBranchId = userStore.assignedSpecialistBranchId;
    }
    _loadSlots();
  }

  Future<void> _loadSlots() async {
    final specialistId = userStore.assignedSpecialistId;
    if (specialistId == null || specialistId == 0) {
      return;
    }
    setState(() {
      isLoadingSlots = true;
      slots = [];
      selectedSlot = null;
    });
    try {
      final date = DateFormat('yyyy-MM-dd').format(selectedDate);
      final result = await fetchClinicAvailability(specialistId: specialistId, date: date);
      setState(() {
        slots = result;
      });
    } catch (e) {
      toast(e.toString());
    } finally {
      setState(() {
        isLoadingSlots = false;
      });
    }
  }

  Future<void> _book() async {
    final specialistId = userStore.assignedSpecialistId;
    final branchId = selectedBranchId ?? userStore.assignedSpecialistBranchId;
    if (specialistId == null || specialistId == 0) {
      toast(appStore.selectedLanguageCode == 'ar' ? 'لا يوجد أخصائي مرتبط بحسابك.' : 'No specialist assigned to your account.');
      return;
    }
    if (branchId == null || branchId == 0) {
      toast(appStore.selectedLanguageCode == 'ar' ? 'يرجى اختيار الفرع.' : 'Please select a branch.');
      return;
    }
    if (selectedSlot == null) {
      toast(appStore.selectedLanguageCode == 'ar' ? 'يرجى اختيار معاد متاح.' : 'Please select a time slot.');
      return;
    }
    setState(() {
      isSubmitting = true;
    });
    try {
      final body = {
        'specialist_id': specialistId,
        'branch_id': branchId,
        'date': DateFormat('yyyy-MM-dd').format(selectedDate),
        'time': selectedSlot,
      };
      await bookClinicAppointment(body);
      toast(appStore.selectedLanguageCode == 'ar' ? 'تم حجز الموعد بنجاح' : 'Appointment booked successfully');
      Navigator.of(context).pop(true);
    } catch (e) {
      toast(e.toString());
    } finally {
      if (mounted) {
        setState(() {
          isSubmitting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final newBookingLabel = appStore.selectedLanguageCode == 'ar' ? 'حجز جديد' : 'New booking';
    return Scaffold(
      appBar: AppBar(
        title: Text(newBookingLabel, style: boldTextStyle(color: Colors.white)),
        backgroundColor: primaryColor,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Column(
        children: [
          CalendarDatePicker(
            initialDate: selectedDate,
            firstDate: DateTime.now(),
            lastDate: DateTime.now().add(const Duration(days: 60)),
            onDateChanged: (value) {
              setState(() {
                selectedDate = value;
              });
              _loadSlots();
            },
          ),
          if (branches.isNotEmpty && (userStore.assignedSpecialistBranchId ?? 0) == 0)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: DropdownButtonFormField<int>(
                value: selectedBranchId,
                items: branches
                    .map((e) => DropdownMenuItem<int>(
                          value: e.id,
                          child: Text(e.name),
                        ))
                    .toList(),
                onChanged: (value) {
                  setState(() {
                    selectedBranchId = value;
                  });
                },
                decoration: InputDecoration(
                  labelText: appStore.selectedLanguageCode == 'ar' ? 'اختر الفرع' : 'Select branch',
                  border: const OutlineInputBorder(),
                ),
              ),
            ),
          Expanded(
            child: isLoadingSlots
                ? const Loader()
                : slots.isEmpty
                    ? Center(
                        child: Text(
                          appStore.selectedLanguageCode == 'ar' ? 'لا توجد مواعيد متاحة في هذا اليوم' : 'No slots available for this day',
                          style: primaryTextStyle(),
                          textAlign: TextAlign.center,
                        ).paddingAll(16),
                      )
                    : GridView.builder(
                        padding: const EdgeInsets.all(16),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3,
                          childAspectRatio: 2.6,
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                        ),
                        itemCount: slots.length,
                        itemBuilder: (context, index) {
                          final slot = slots[index];
                          final isSelected = selectedSlot == slot.time;
                          return ElevatedButton(
                            onPressed: slot.isAvailable
                                ? () {
                                    setState(() {
                                      selectedSlot = slot.time;
                                    });
                                  }
                                : null,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: slot.isAvailable
                                  ? (isSelected ? primaryColor : Colors.white)
                                  : Colors.grey.shade200,
                              foregroundColor: slot.isAvailable
                                  ? (isSelected ? Colors.white : primaryColor)
                                  : Colors.grey,
                              side: BorderSide(color: primaryColor),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            child: Text(slot.time),
                          );
                        },
                      ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: isSubmitting ? null : _book,
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryColor,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
                child: isSubmitting
                    ? const Loader(color: Colors.white, size: 20)
                    : Text(appStore.selectedLanguageCode == 'ar' ? 'حفظ' : languages.lblSave,
                        style: boldTextStyle(color: Colors.white, size: 16)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
