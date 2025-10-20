import 'package:flutter/material.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/loader_widget.dart';
import 'package:mighty_fitness/extensions/text_styles.dart';
import 'package:mighty_fitness/main.dart';
import 'package:mighty_fitness/models/clinic_models.dart';
import 'package:mighty_fitness/network/rest_api.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_images.dart';
import 'package:mighty_fitness/extensions/no_data_widget.dart';

class MyBookingsScreen extends StatefulWidget {
  const MyBookingsScreen({Key? key}) : super(key: key);

  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

class _MyBookingsScreenState extends State<MyBookingsScreen> {
  late Future<ClinicAppointmentsResponse> _future;

  @override
  void initState() {
    super.initState();
    _future = fetchClinicAppointments();
  }

  @override
  Widget build(BuildContext context) {
    final myBookingsLabel = appStore.selectedLanguageCode == 'ar' ? 'حجوزاتى' : 'My bookings';
    return Scaffold(
      appBar: AppBar(
        title: Text(myBookingsLabel, style: boldTextStyle(color: Colors.white)),
        backgroundColor: primaryColor,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: FutureBuilder<ClinicAppointmentsResponse>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return Loader();
          }

          if (snapshot.hasError) {
            return Center(
              child: Text(
                appStore.selectedLanguageCode == 'ar' ? 'حدث خطأ أثناء تحميل المواعيد' : 'Failed to load appointments',
                style: primaryTextStyle(),
                textAlign: TextAlign.center,
              ).paddingAll(16),
            );
          }

          final data = snapshot.data;
          if (data == null || (data.upcoming.isEmpty && data.past.isEmpty)) {
            return NoDataWidget(
              image: no_data_found,
              title: appStore.selectedLanguageCode == 'ar' ? 'لا توجد حجوزات بعد' : 'No bookings yet',
            );
          }

          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = fetchClinicAppointments();
              });
            },
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (data.upcoming.isNotEmpty) ...[
                  Text(appStore.selectedLanguageCode == 'ar' ? 'المواعيد القادمة' : 'Upcoming', style: boldTextStyle(size: 18)),
                  const SizedBox(height: 12),
                  ...data.upcoming.map((e) => _AppointmentTile(summary: e)),
                  const SizedBox(height: 24),
                ],
                if (data.past.isNotEmpty) ...[
                  Text(appStore.selectedLanguageCode == 'ar' ? 'المواعيد السابقة' : 'Past', style: boldTextStyle(size: 18)),
                  const SizedBox(height: 12),
                  ...data.past.map((e) => _AppointmentTile(summary: e)),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}

class _AppointmentTile extends StatelessWidget {
  final ClinicAppointmentSummary summary;

  const _AppointmentTile({required this.summary});

  @override
  Widget build(BuildContext context) {
    final subtitle = [
      if (summary.branchName != null) summary.branchName!,
      if (summary.specialistName != null) summary.specialistName!,
    ].join(' • ');

    final isArabic = appStore.selectedLanguageCode == 'ar';
    final statusLabels = {
      'confirmed': isArabic ? 'مثبت' : 'Confirmed',
      'pending': isArabic ? 'لم يتم الرد' : 'No Response',
      'rescheduled': isArabic ? 'تم التأجيل' : 'Rescheduled',
      'cancelled': isArabic ? 'إلغاء' : 'Cancelled',
      'wrong_number': isArabic ? 'رقم خاطئ' : 'Wrong Number',
      'not_subscribed': isArabic ? 'لم يشترك' : 'Not Subscribed',
      'subscribed': isArabic ? 'اشترك' : 'Subscribed',
      'other': isArabic ? 'أخرى' : 'Other',
    };

    final statusColors = {
      'confirmed': Colors.green,
      'pending': Colors.orange,
      'rescheduled': Colors.blue,
      'cancelled': Colors.red,
      'wrong_number': Colors.grey,
      'not_subscribed': Colors.grey,
      'subscribed': primaryColor,
      'other': Colors.teal,
    };

    final statusColor = statusColors[summary.status] ?? primaryColor;
    final statusLabel = statusLabels[summary.status] ?? summary.status;

    return Container(
      decoration: BoxDecoration(
        color: context.scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: context.dividerColor),
      ),
      padding: const EdgeInsets.all(16),
      margin: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('${summary.date} • ${summary.time}', style: boldTextStyle(size: 16)),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  statusLabel,
                  style: primaryTextStyle(color: statusColor, size: 12),
                ),
              ),
            ],
          ),
          if (subtitle.isNotEmpty)
            Text(subtitle, style: secondaryTextStyle()).paddingTop(8),
          Text(summary.type.toUpperCase(), style: secondaryTextStyle(size: 12)).paddingTop(4),
        ],
      ),
    );
  }
}
