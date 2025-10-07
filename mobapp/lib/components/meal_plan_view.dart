import 'package:flutter/material.dart';

import '../extensions/decorations.dart';
import '../extensions/text_styles.dart';
import '../main.dart';
import '../models/diet_response.dart';
import '../utils/app_common.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';

class MealPlanView extends StatelessWidget {
  final List<MealPlanDay> days;
  final EdgeInsetsGeometry? padding;

  const MealPlanView({Key? key, required this.days, this.padding}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    if (days.isEmpty) {
      final noDataText = appStore.selectedLanguageCode == 'ar'
          ? 'لا توجد خطة وجبات متاحة.'
          : 'No meal plan available yet.';

      return Text(noDataText, style: secondaryTextStyle());
    }

    Widget content = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: days.map((day) => _buildDayCard(context, day)).toList(),
    );

    if (padding != null) {
      content = Padding(padding: padding!, child: content);
    }

    return content;
  }

  Widget _buildDayCard(BuildContext context, MealPlanDay day) {
    final dayTitle = '${languages.lblDay} ${day.dayNumber}';

    return Container(
      margin: EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: context.cardColor,
        borderRadius: radius(16),
        boxShadow: defaultBoxShadow(spreadRadius: 0, blurRadius: 8),
      ),
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(dayTitle, style: boldTextStyle(size: 16)),
          12.height,
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: day.meals.map((meal) => _buildMealSection(context, meal)).toList(),
          ),
          12.height,
          _buildTotalsRow(context, day.totals),
        ],
      ),
    );
  }

  Widget _buildMealSection(BuildContext context, MealPlanMeal meal) {
    final mealTitle = appStore.selectedLanguageCode == 'ar'
        ? 'الوجبة ${meal.mealNumber}'
        : 'Meal ${meal.mealNumber}';

    return Container(
      margin: EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: context.scaffoldBackgroundColor,
        borderRadius: radius(12),
      ),
      padding: EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(mealTitle, style: boldTextStyle()),
          12.height,
          Column(
            children: meal.ingredients.map((ingredient) => _buildIngredientTile(context, ingredient)).toList(),
          ),
          12.height,
          _buildTotalsRow(context, meal.totals, showLabel: true),
        ],
      ),
    );
  }

  Widget _buildIngredientTile(BuildContext context, MealPlanIngredientDetail ingredient) {
    final hasImage = ingredient.image.validate().isNotEmpty;
    final quantityLabel = appStore.selectedLanguageCode == 'ar' ? 'الكمية' : 'Quantity';
    final gramsLabel = appStore.selectedLanguageCode == 'ar' ? 'جرام' : 'g';
    final macroText = '${languages.lblProtein}: ${_formatDouble(ingredient.protein)} $gramsLabel\n'
        '${languages.lblCarbs}: ${_formatDouble(ingredient.carbs)} $gramsLabel\n'
        '${languages.lblFat}: ${_formatDouble(ingredient.fat)} $gramsLabel\n'
        '${languages.lblCalories}: ${_formatDouble(ingredient.calories)} ${languages.lblKcal}';

    return Container(
      margin: EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (hasImage)
            ClipRRect(
              borderRadius: radius(32),
              child: cachedImage(
                ingredient.image.validate(),
                height: 48,
                width: 48,
                fit: BoxFit.cover,
              ),
            )
          else
            Container(
              height: 48,
              width: 48,
              decoration: BoxDecoration(
                color: context.dividerColor,
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.restaurant_menu, color: context.iconColor, size: 22),
            ),
          12.width,
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(ingredient.title.validate(), style: primaryTextStyle()),
              4.height,
              Text('$quantityLabel: ${_formatDouble(ingredient.quantity)}', style: secondaryTextStyle()),
              4.height,
              Text(macroText, style: secondaryTextStyle(size: 12)),
            ],
          ).expand(),
        ],
      ),
    );
  }

  Widget _buildTotalsRow(BuildContext context, MealPlanTotals totals, {bool showLabel = false}) {
    final gramsLabel = appStore.selectedLanguageCode == 'ar' ? 'جرام' : 'g';
    final label = showLabel
        ? (appStore.selectedLanguageCode == 'ar' ? 'إجمالي الوجبة' : 'Meal totals')
        : (appStore.selectedLanguageCode == 'ar' ? 'إجمالي اليوم' : 'Day totals');

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: boldTextStyle()),
        8.height,
        Wrap(
          spacing: 12,
          runSpacing: 8,
          children: [
            _buildTotalChip(context, languages.lblProtein, '${_formatDouble(totals.protein)} $gramsLabel'),
            _buildTotalChip(context, languages.lblCarbs, '${_formatDouble(totals.carbs)} $gramsLabel'),
            _buildTotalChip(context, languages.lblFat, '${_formatDouble(totals.fat)} $gramsLabel'),
            _buildTotalChip(context, languages.lblCalories, '${_formatDouble(totals.calories)} ${languages.lblKcal}'),
          ],
        ),
      ],
    );
  }

  Widget _buildTotalChip(BuildContext context, String label, String value) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: context.dividerColor.withOpacity(0.2),
        borderRadius: radius(20),
      ),
      child: RichText(
        text: TextSpan(
          style: secondaryTextStyle(),
          children: [
            TextSpan(text: '$label: ', style: boldTextStyle(size: 12)),
            TextSpan(text: value, style: secondaryTextStyle(size: 12)),
          ],
        ),
      ),
    );
  }

  String _formatDouble(double value) {
    if (value % 1 == 0) {
      return value.toStringAsFixed(0);
    }

    return value.toStringAsFixed(2);
  }
}
