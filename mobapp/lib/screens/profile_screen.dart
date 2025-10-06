import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_mobx/flutter_mobx.dart';
import 'package:mighty_fitness/screens/home_screen.dart';
import 'package:mighty_fitness/screens/workout_history_screen.dart';
import '../screens/reminder_screen.dart';
import '../extensions/common.dart';
import '../screens/subscription_detail_screen.dart';
import '../../extensions/constants.dart';
import '../../extensions/extension_util/context_extensions.dart';
import '../../extensions/extension_util/int_extensions.dart';
import '../../extensions/extension_util/string_extensions.dart';
import '../../extensions/extension_util/widget_extensions.dart';
import '../../extensions/system_utils.dart';
import '../../screens/assign_screen.dart';
import '../../screens/blog_screen.dart';
import '../../screens/edit_profile_screen.dart';
import '../../screens/setting_screen.dart';
import '../../screens/sign_in_screen.dart';
import '../extensions/colors.dart';
import '../extensions/confirmation_dialog.dart';
import '../extensions/decorations.dart';
import '../extensions/loader_widget.dart';
import '../extensions/text_styles.dart';
import '../main.dart';
import '../service/auth_service.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';
import '../utils/app_images.dart';
import 'about_app_screen.dart';
import 'favourite_screen.dart';
import '../models/diet_response.dart';
import '../models/product_response.dart';
import '../models/workout_detail_response.dart';
import '../network/rest_api.dart';

class ProfileScreen extends StatefulWidget {
  @override
  _ProfileScreenState createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  List<WorkoutDetailModel> _favouriteWorkouts = [];
  List<DietModel> _favouriteDiets = [];
  List<ProductModel> _favouriteProducts = [];
  bool _isFetchingFavourites = false;

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
  }

  @override
  void initState() {
    super.initState();
    _fetchFavouriteData();
  }

  Widget mOtherInfo(String title, String value, String heading) {
    return Container(
      decoration: boxDecorationWithRoundedCorners(borderRadius: radius(12), backgroundColor: primaryOpacity),
      padding: EdgeInsets.symmetric(vertical: 10, horizontal: 10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          RichText(
            textAlign: TextAlign.center,
            text: TextSpan(
              children: [
                TextSpan(text: value, style: boldTextStyle(size: 18, color: primaryColor)),
                WidgetSpan(child: Padding(padding: EdgeInsets.only(right: 4))),
                TextSpan(text: heading, style: boldTextStyle(size: 14, color: primaryColor)),
              ],
            ),
          ),
          6.height,
          Text(title, style: secondaryTextStyle(size: 12, color: textColor)),
        ],
      ),
    );
  }

  Future<void> _fetchFavouriteData() async {
    if (!userStore.isLoggedIn) return;

    setState(() {
      _isFetchingFavourites = true;
    });

    await getUserDataApi(id: userStore.userId).then((value) {
      _favouriteWorkouts = value.data?.favouriteWorkouts ?? [];
      _favouriteDiets = value.data?.favouriteDiets ?? [];
      _favouriteProducts = value.data?.favouriteProducts ?? [];
      cartCountNotifier.value = value.data?.cartItemCount ?? cartCountNotifier.value;
      setState(() {});
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isFetchingFavourites = false;
        });
      }
    });
  }

  Widget _buildFavouriteSection() {
    if (_isFetchingFavourites) {
      return Loader().paddingAll(24);
    }

    if (_favouriteWorkouts.isEmpty &&
        _favouriteDiets.isEmpty &&
        _favouriteProducts.isEmpty) {
      return Container(
        width: double.infinity,
        padding: EdgeInsets.all(24),
        decoration: boxDecorationWithRoundedCorners(
            borderRadius: radius(14),
            backgroundColor:
                appStore.isDarkMode ? socialBackground : context.cardColor),
        child: Text(languages.lblNoFoundData,
                style: secondaryTextStyle(), textAlign: TextAlign.center)
            .center(),
      ).paddingSymmetric(horizontal: 16);
    }

    return Container(
      width: double.infinity,
      margin: EdgeInsets.symmetric(horizontal: 16),
      padding: EdgeInsets.all(16),
      decoration: boxDecorationWithRoundedCorners(
        borderRadius: radius(14),
        backgroundColor:
            appStore.isDarkMode ? socialBackground : context.cardColor,
        boxShadow: [
          BoxShadow(
            color: shadowColorGlobal,
            blurRadius: 10,
            spreadRadius: 2,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(languages.lblFavoriteWorkoutAndNutristions,
              style: boldTextStyle(size: 18)),
          12.height,
          if (_favouriteWorkouts.isNotEmpty)
            _buildHorizontalFavouriteList<WorkoutDetailModel>(
              title: languages.lblWorkouts,
              items: _favouriteWorkouts,
              itemBuilder: (workout) => _FavouriteItemCard(
                image: workout.workoutImage,
                title: workout.title,
                subtitle: workout.levelTitle,
              ),
            ),
          if (_favouriteDiets.isNotEmpty)
            _buildHorizontalFavouriteList<DietModel>(
              title: languages.lblDiet,
              items: _favouriteDiets,
              itemBuilder: (diet) => _FavouriteItemCard(
                image: diet.dietImage,
                title: diet.title,
                subtitle: diet.calories,
              ),
            ),
          if (_favouriteProducts.isNotEmpty)
            _buildHorizontalFavouriteList<ProductModel>(
              title: languages.lblProductList,
              items: _favouriteProducts,
              itemBuilder: (product) => _FavouriteItemCard(
                image: product.productImage,
                title: product.title,
                subtitle:
                    '${userStore.currencySymbol.validate()}${(product.finalPrice ?? product.price ?? 0).toStringAsFixed(2)}',
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildHorizontalFavouriteList<T>({
    required String title,
    required List<T> items,
    required _FavouriteItemCard Function(T) itemBuilder,
  }) {
    if (items.isEmpty) return SizedBox();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: boldTextStyle()),
        8.height,
        SizedBox(
          height: 150,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: EdgeInsets.only(bottom: 4),
            itemCount: items.length,
            separatorBuilder: (_, __) => 12.width,
            itemBuilder: (context, index) {
              return itemBuilder(items[index]);
            },
          ),
        ),
        12.height,
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion(
      value: SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: appStore.isDarkMode ? Brightness.light : Brightness.light,
        systemNavigationBarIconBrightness: appStore.isDarkMode ? Brightness.light : Brightness.light,
      ),
      child: Scaffold(
        backgroundColor: appStore.isDarkMode ? cardDarkColor : cardLightColor,
        body: Observer(
          builder: (context) {
            return SingleChildScrollView(
              child: Column(
                children: [
                  Stack(
                    children: [
                      Container(height: context.height() * 0.4, color: primaryColor),
                      Align(
                        alignment: Alignment.center,
                        child: Text(languages.lblProfile, style: boldTextStyle(size: 20, color: white)).paddingTop(context.statusBarHeight + 16),
                      ),
                      Container(
                        margin: EdgeInsets.only(top: context.height() * 0.2),
                        height: context.height() * 0.9,
                        decoration: boxDecorationWithRoundedCorners(
                          backgroundColor: appStore.isDarkMode ? context.scaffoldBackgroundColor : context.cardColor,
                          borderRadius: radiusOnly(topRight: defaultRadius, topLeft: defaultRadius),
                        ),
                      ),
                      Container(
                        margin: EdgeInsets.only(top: context.height() * 0.1, right: 16, left: 16),
                        child: Column(
                          children: [
                            16.height,
                            Container(
                              padding: EdgeInsets.symmetric(vertical: 20, horizontal: 4),
                              decoration: boxDecorationWithRoundedCorners(
                                  backgroundColor: appStore.isDarkMode ? socialBackground : context.cardColor,
                                  boxShadow: [BoxShadow(color: shadowColorGlobal, offset: Offset(0, 1), spreadRadius: 2, blurRadius: 10, blurStyle: BlurStyle.outer)],
                                  borderRadius: radius(14)),
                              child: Column(
                                children: [
                                  Row(
                                    children: [
                                      Stack(
                                        alignment: Alignment.bottomRight,
                                        children: [
                                          Container(
                                            decoration: boxDecorationWithRoundedCorners(boxShape: BoxShape.circle, border: Border.all(width: 2, color: primaryColor)),
                                            child: cachedImage(userStore.profileImage.validate(), height: 65, width: 65, fit: BoxFit.cover).cornerRadiusWithClipRRect(100).paddingAll(1),
                                          ),
                                          Container(
                                            decoration: boxDecorationWithRoundedCorners(boxShape: BoxShape.circle, border: Border.all(width: 2, color: white), backgroundColor: primaryColor),
                                            padding: EdgeInsets.all(4),
                                            child: Image.asset(ic_edit, color: white, height: 14, width: 14),
                                          )
                                        ],
                                      ),
                                      12.width,
                                      Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(userStore.fName.validate().capitalizeFirstLetter() + " " + userStore.lName.capitalizeFirstLetter(), style: boldTextStyle(size: 20)),
                                          2.height,
                                          Text(userStore.email.validate(), style: secondaryTextStyle()),
                                        ],
                                      ).expand(),
                                    ],
                                  ).paddingSymmetric(horizontal: 16).onTap(() async {
                                    bool? res = await EditProfileScreen().launch(context);
                                    if (res == true) {
                                      setState(() {});
                                    }
                                  }),
                                  20.height,
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                                    children: [
                                      mOtherInfo(languages.lblWeight, userStore.weight.isEmptyOrNull ? "-" : userStore.weight.validate(),
                                              userStore.weight.isEmptyOrNull ? "" : userStore.weightUnit.validate())
                                          .expand(),
                                      12.width,
                                      mOtherInfo(languages.lblHeight, userStore.height.isEmptyOrNull ? "-" : userStore.height.validate(),
                                              userStore.height.isEmptyOrNull ? "" : userStore.heightUnit.validate())
                                          .expand(),
                                      12.width,
                                      mOtherInfo(languages.lblAge, userStore.age.isEmptyOrNull ? "-" : userStore.age.validate(), userStore.age.isEmptyOrNull ? "" : languages.lblYear).expand(),
                                    ],
                                  ).paddingSymmetric(horizontal: 16),
                                ],
                              ),
                            ),
                            8.height,
                            _buildFavouriteSection(),
                            16.height,
                            mOption(ic_blog, languages.lblBlog, () {
                              BlogScreen().launch(context);
                            }),
                            Divider(height: 0, color: grayColor),

                            // ///TODO
                            // mOption(ic_blog, "Videos", () {
                            //   VideoScreen().launch(context);
                            // }),
                            Divider(height: 0, color: grayColor),

                            if (userStore.subscription == "1")
                              mOption(ic_subscription_plan, languages.lblSubscriptionPlans, () {
                                SubscriptionDetailScreen().launch(context);
                              }),
                            Divider(height: 0, color: grayColor),
                            mOption(ic_fav_outline, languages.lblFavoriteWorkoutAndNutristions, () {
                              FavouriteScreen(index: 0)
                                  .launch(context)
                                  .then((value) => _fetchFavouriteData());
                            }),
                            Divider(height: 0, color: grayColor),
                            mOption(ic_reminder, languages.lblDailyReminders, () {
                              ReminderScreen().launch(context);
                            }),
                            Divider(height: 0, color: grayColor),
                            mOption(ic_assigned, languages.lblPlan, () {
                              AssignScreen().launch(context);
                            }),
                            Divider(height: 0, color: grayColor),
                            mOption(ic_setting, languages.lblSettings, () {
                              SettingScreen().launch(context, pageRouteAnimation: PageRouteAnimation.Fade);
                            }),
                            Divider(height: 0, color: grayColor),
                            mOption(ic_info, languages.lblAboutApp, () {
                              AboutAppScreen().launch(context, pageRouteAnimation: PageRouteAnimation.Fade);
                            }),
                            Divider(height: 0, color: grayColor),
                            mOption(ic_report, 'Workout History', () {

                              WorkoutHistoryScreen().launch(context, pageRouteAnimation: PageRouteAnimation.Fade);
                            }),
                            Divider(height: 0, color: grayColor),
                            mOption(ic_logout, languages.lblLogout, () {
                              showConfirmDialogCustom(context,
                                  dialogType: DialogType.DELETE,
                                  title: languages.lblLogoutMsg,
                                  primaryColor: primaryColor,
                                  positiveText: languages.lblLogout,
                                  image: ic_logout, onAccept: (buildContext) {
                                logout(context, onLogout: () {
                                  isFirstTimeGraph = false;
                                  SignInScreen().launch(context, isNewTask: true);
                                });
                                finish(context);
                              });
                            }),
                            20.height,
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

class _FavouriteItemCard extends StatelessWidget {
  final String? image;
  final String? title;
  final String? subtitle;

  const _FavouriteItemCard({this.image, this.title, this.subtitle});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 130,
      decoration: boxDecorationWithRoundedCorners(
        borderRadius: radius(12),
        backgroundColor:
            appStore.isDarkMode ? socialBackground : context.cardColor,
        boxShadow: [
          BoxShadow(
            color: shadowColorGlobal,
            blurRadius: 6,
            offset: Offset(0, 2),
          )
        ],
      ),
      padding: EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          cachedImage(image,
                  height: 70, width: double.infinity, fit: BoxFit.cover)
              .cornerRadiusWithClipRRect(10),
          8.height,
          Text(title.validate(),
              style: boldTextStyle(size: 13),
              maxLines: 2,
              overflow: TextOverflow.ellipsis),
          if (subtitle.validate().isNotEmpty)
            Text(subtitle.validate(),
                    style: secondaryTextStyle(size: 11),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis)
                .paddingTop(4),
        ],
      ),
    );
  }
}
