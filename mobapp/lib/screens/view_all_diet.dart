import 'package:flutter/material.dart';
import '../../extensions/extension_util/string_extensions.dart';
import '../../extensions/extension_util/widget_extensions.dart';
import '../../extensions/widgets.dart';
import '../components/adMob_component.dart';
import '../components/featured_diet_component.dart';
import '../components/meal_plan_view.dart';
import '../extensions/animatedList/animated_list_view.dart';
import '../extensions/loader_widget.dart';
import '../main.dart';
import '../models/category_diet_response.dart';
import '../models/diet_response.dart';
import '../network/rest_api.dart';
import 'no_data_screen.dart';

class ViewAllDiet extends StatefulWidget {
  final bool? isFeatured;
  final bool? isCategory;
  final int? mCategoryId;
  final String? mTitle;
  final bool? isAssign;
  final bool? isFav;

  ViewAllDiet({this.isFeatured = false, this.isCategory, this.mCategoryId, this.mTitle, this.isFav = false, this.isAssign = false});

  @override
  _ViewAllDietState createState() => _ViewAllDietState();
}

class _ViewAllDietState extends State<ViewAllDiet> {
  ScrollController scrollController = ScrollController();

  List<DietModel> mDietList = [];

  CategoryDietModel? mCategoryDietModel;

  int page = 1;
  int? numPage;

  bool isLastPage = false;

  @override
  void initState() {
    super.initState();
    init();
    scrollController.addListener(() {
      if (scrollController.position.pixels == scrollController.position.maxScrollExtent && !appStore.isLoading) {
        if (page < numPage!) {
          page++;
          init();
        }
      }
    });
  }

  void init() async {
    getDietData();
  }

  Future<void> getDietData() async {
    appStore.setLoading(true);
    await getDietApi(page: page, widget.isFeatured == true ? "yes" : "no", widget.isCategory, isAssign: widget.isAssign, categoryId: widget.mCategoryId, isFav: widget.isFav).then((value) {
      appStore.setLoading(false);
      numPage = value.pagination!.totalPages;
      isLastPage = false;
      if (page == 1) {
        mDietList.clear();
      }
      Iterable it = value.data!;
      it.map((e) => mDietList.add(e)).toList();
      setState(() {});
    }).catchError((e) {
      isLastPage = true;
      appStore.setLoading(false);
      setState(() {});
    });
  }



  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: widget.isAssign == true || widget.isFav == true ? PreferredSize(preferredSize: Size.fromHeight(0), child: SizedBox()) : appBarWidget(widget.mTitle.validate(), context: context),
      body: Stack(
        children: [
          mDietList.isNotEmpty
              ? AnimatedListView(
                  controller: scrollController,
                  itemCount: mDietList.length,
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: widget.isFav == true || widget.isAssign == true ? 16 : 8),
                  shrinkWrap: true,
                  itemBuilder: (context, index) {
                    if (widget.isAssign == true) {
                      return _AssignedDietCard(diet: mDietList[index])
                          .paddingOnly(bottom: 16);
                    }

                    return FeaturedDietComponent(
                      isList: true,
                      mDietModel: mDietList[index],
                      onCall: () {
                        if (widget.isFav == true) {
                          mDietList.clear();
                          getDietData();
                        }
                      },
                    );
                  },
                )
              : (widget.isAssign == true
                      ? _buildNoAssignedDietView()
                      : NoDataScreen(mTitle: languages.lblResultNoFound))
                  .visible(!appStore.isLoading),
          Loader().center().visible(appStore.isLoading)
        ],
      ),
      bottomNavigationBar: userStore.adsBannerDetailShowBannerAdsOnDiet == 1 && userStore.isSubscribe == 0 ? showBannerAds(context) : SizedBox(),
    );
  }
}

class _AssignedDietCard extends StatelessWidget {
  final DietModel diet;

  const _AssignedDietCard({Key? key, required this.diet}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final hasImage = diet.dietImage.validate().isNotEmpty;
    final customPlanLabel = appStore.selectedLanguageCode == 'ar' ? 'خطة مخصصة' : 'Custom plan';

    return Container(
      decoration: BoxDecoration(
        color: context.cardColor,
        borderRadius: radius(16),
        boxShadow: defaultBoxShadow(spreadRadius: 0, blurRadius: 8),
      ),
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (hasImage)
                ClipRRect(
                  borderRadius: radius(12),
                  child: cachedImage(
                    diet.dietImage.validate(),
                    height: 72,
                    width: 72,
                    fit: BoxFit.cover,
                  ),
                ),
              if (hasImage) 12.width,
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(diet.title.validate(), style: boldTextStyle(size: 16)),
                    4.height,
                    if (diet.calories.validate().isNotEmpty)
                      Text('${languages.lblCalories}: ${diet.calories.validate()}', style: secondaryTextStyle()),
                    if (diet.hasCustomPlan == true)
                      Container(
                        margin: EdgeInsets.only(top: 8),
                        padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: primaryColor.withOpacity(0.1),
                          borderRadius: radius(12),
                        ),
                        child: Text(customPlanLabel, style: primaryTextStyle(size: 12, color: primaryColor)),
                      ),
                  ],
                ),
              ),
            ],
          ),
          16.height,
          MealPlanView(days: diet.mealPlan ?? [], padding: EdgeInsets.zero),
        ],
      ),
    );
  }
}

Widget _buildNoAssignedDietView() {
  final message = appStore.selectedLanguageCode == 'ar'
      ? 'لم يتم إضافة دايت حتى الآن.'
      : 'No diet has been assigned yet.';

  return Center(child: Text(message, style: secondaryTextStyle()));
}
