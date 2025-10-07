import 'package:flutter/material.dart';
import 'package:mighty_fitness/extensions/colors.dart';
import 'package:mighty_fitness/extensions/decorations.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/int_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/string_extensions.dart';
import 'package:mighty_fitness/extensions/text_styles.dart';
import 'package:mighty_fitness/main.dart';
import 'package:mighty_fitness/models/manual_exercise_response.dart';

class ManualWorkoutHistoryTable extends StatelessWidget {
  final List<ManualExerciseModel> entries;
  final String? title;
  final Widget? trailing;
  final EdgeInsetsGeometry? margin;

  const ManualWorkoutHistoryTable({
    super.key,
    required this.entries,
    this.title,
    this.trailing,
    this.margin,
  });

  @override
  Widget build(BuildContext context) {
    if (entries.isEmpty) {
      return const SizedBox();
    }

    final headerTextStyle = boldTextStyle(color: appStore.isDarkMode ? Colors.white : context.iconColor);
    final bodyTextStyle = secondaryTextStyle(color: appStore.isDarkMode ? Colors.white70 : context.iconColor.withOpacity(0.8));

    final bool isArabic = appStore.selectedLanguageCode == 'ar';

    return Container(
      margin: margin ?? const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: boxDecorationWithRoundedCorners(
        borderRadius: radius(16),
        backgroundColor: appStore.isDarkMode ? context.cardColor : Colors.white,
        border: Border.all(color: context.dividerColor.withOpacity(0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (title != null || trailing != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  if (title != null)
                    Text(title!, style: boldTextStyle(size: 16)),
                  if (trailing != null) trailing!,
                ],
              ),
            ),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: DataTable(
              headingTextStyle: headerTextStyle,
              dataTextStyle: bodyTextStyle,
              dividerThickness: 0.2,
              columns: [
                DataColumn(label: Text(isArabic ? 'تاريخ التنفيذ' : 'Date')),
                DataColumn(label: Text(isArabic ? 'النشاط' : 'Activity')),
                DataColumn(label: Text(isArabic ? 'المدة (دقائق)' : 'Duration (min)')),
              ],
              rows: entries
                  .map(
                    (item) => DataRow(
                      cells: [
                        DataCell(Text(item.performedOn.validate(value: '-'))),
                        DataCell(Text(item.activity.validate(value: '-'))),
                        DataCell(Text(
                            item.duration != null ? item.duration!.toStringAsFixed(item.duration! % 1 == 0 ? 0 : 1) : '-')),
                      ],
                    ),
                  )
                  .toList(),
            ),
          ),
        ],
      ),
    );
  }
}
