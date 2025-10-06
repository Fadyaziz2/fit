import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_mobx/flutter_mobx.dart';
import 'package:flutter_vector_icons/flutter_vector_icons.dart';
import 'package:intl/intl.dart';
import 'package:marquee/marquee.dart';
import 'package:mighty_fitness/extensions/shared_pref.dart';
import 'package:mighty_fitness/screens/castTester.dart';
import 'package:mighty_fitness/utils/app_constants.dart';
import '../../components/level_component.dart';
import '../../extensions/decorations.dart';
import '../../extensions/extension_util/context_extensions.dart';
import '../../extensions/extension_util/int_extensions.dart';
import '../../extensions/extension_util/string_extensions.dart';
import '../../extensions/extension_util/widget_extensions.dart';
import '../../extensions/widgets.dart';
import '../../main.dart';
import '../../screens/view_body_part_screen.dart';
import '../../screens/view_equipment_screen.dart';
import '../../screens/view_level_screen.dart';
import '../../utils/app_colors.dart';
import '../components/body_part_component.dart';
import '../components/equipment_component.dart';
import '../components/workout_component.dart';
import '../extensions/app_text_field.dart';
import '../extensions/common.dart';
import '../extensions/horizontal_list.dart';
import '../extensions/loader_widget.dart';
import '../extensions/text_styles.dart';
import '../models/dashboard_response.dart';
import '../models/product_response.dart';
import '../network/rest_api.dart';
import '../screens/edit_profile_screen.dart';
import '../screens/search_screen.dart';
import '../screens/product_screen.dart';
import '../screens/cart_screen.dart';
import '../utils/app_common.dart';
import '../utils/app_images.dart';
import 'filter_workout_screen.dart';
import 'notification_screen.dart';
import '../components/product_component.dart';

bool? isFirstTimeGraph = false;

class HomeScreen extends StatefulWidget {
  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen>{
  ScrollController mScrollController = ScrollController();
  TextEditingController mSearchCont = TextEditingController();
  String? mSearchValue = "";
  bool _showClearButton = false;

  @override
  void initState() {
    Future.delayed(Duration.zero).then((val) {
      print("------------75>>>>${getBoolAsync(CRISP_CHAT_ENABLED)}");
      getUserDetailsApiCall();
      if (isFirstTimeGraph == false) {
        graphGet();
      }
    });

    super.initState();

  }


  getUserDetailsApiCall() async {
    await getUSerDetail(context, userStore.userId).whenComplete(() {});
  }

  @override
  void dispose() {
    super.dispose();
  }

  init() async {
    double weightInPounds = userStore.weight.toDouble();
    double weightInKilograms = poundsToKilograms(weightInPounds);
    var saveWeightGraph = userStore.weightStoreGraph.replaceAll('user', '').trim();

    print("------------175>>>>${weightInKilograms.toStringAsFixed(2)}");
    print("------------176>>>>${saveWeightGraph}");
    print("------------177>>>>${userStore.weight}");

    //visible(getStringAsync(TERMS_SERVICE).isNotEmpty)
  }

  Future<void> graphSave() async {
    appStore.setLoading(true);
    Map? req;
    double weightInPounds = userStore.weight.toDouble();
    double weightInKilograms = poundsToKilograms(weightInPounds);
    if (userStore.weightUnit == 'lbs') {
      if (userStore.weightId.isNotEmpty) {
        req = {"id": '${userStore.weightId}', "value": '${weightInKilograms.toStringAsFixed(2)} user', "type": 'weight', "unit": 'kg', "date": DateFormat('yyyy-MM-dd').format(DateTime.now())};
      } else {
        req = {"value": '${weightInKilograms.toStringAsFixed(2)} user', "type": 'weight', "unit": 'kg', "date": DateFormat('yyyy-MM-dd').format(DateTime.now())};
      }
    } else {
      if (userStore.weightId.isNotEmpty) {
        req = {"id": '${userStore.weightId}', "value": '${userStore.weight} user', "type": 'weight', "unit": 'kg', "date": DateFormat('yyyy-MM-dd').format(DateTime.now())};
      } else {
        req = {"value": '${userStore.weight} user', "type": 'weight', "unit": 'kg', "date": DateFormat('yyyy-MM-dd').format(DateTime.now())};
      }
    }
    await setProgressApi(req).then((value) async {
      await graphGet();
    }).catchError((e, s) {
      appStore.setLoading(false);
      print('78--${e.toString()}');
      print('79--${s.toString()}');
    });
  }

  Future<void> graphGet() async {
    getProgressApi(METRICS_WEIGHT).then((value) {
      print("------------------99>>>${value.data}");
      print("------------------100>>>${userStore.weightUnit}");
      double weightInKilograms = poundsToKilograms(userStore.weight.toDouble());

      value.data?.forEach((data) {
        if (data.value!.contains('user')) {
          userStore.setWeightId(data.id.toString());
          userStore.setWeightGraph(data.value ?? '');
        }
      });

      if (value.data!.isEmpty) {
        graphSave();
      } else {
        value.data?.forEach((data) {
          if (data.value!.contains('user')) {
            print("------------------106>>>${userStore.weightStoreGraph}");
            print("------------------107>>>${weightInKilograms.toStringAsFixed(2)}");

            if (userStore.weightStoreGraph != null) {
              if (userStore.weightUnit == 'lbs') {
                if (userStore.weightStoreGraph.replaceAll('user', '').trim() != weightInKilograms.toStringAsFixed(2)) {
                  graphSave();
                }
              } else {
                if (userStore.weightStoreGraph.replaceAll('user', '').trim() != userStore.weight) {
                  graphSave();
                }
              }

              /*  if(userStore.weightStoreGraph.replaceAll('user', '').trim()!=weightInKilograms.toStringAsFixed(2)){
                graphSave();
              }*/
            }
          } else {
            appStore.setLoading(false);
          }
          userStore.setWeightId(data.id.toString());
          userStore.setWeightGraph(data.value ?? '');
          isFirstTimeGraph = true;

          appStore.setLoading(false);
        });
      }
    }).catchError((e, s) {
      appStore.setLoading(false);
    });
  }

  Future<void> _toggleProductFavourite(ProductModel product) async {
    if (product.id == null) return;
    appStore.setLoading(true);
    Map req = {"product_id": product.id};
    await setProductFavApi(req).then((value) {
      toast(value.message);
      product.isFavourite = !(product.isFavourite ?? false);
      setState(() {});
    }).catchError((e) {
      print(e);
      appStore.setLoading(false);
    }).whenComplete(() {
      appStore.setLoading(false);
    });
  }

  Future<void> _addProductToCart(ProductModel product) async {
    if (product.id == null) return;
    appStore.setLoading(true);
    Map req = {"product_id": product.id};
    await addToCartApi(req).then((value) {
      toast(value.message);
      product.isInCart = true;
      product.cartQuantity = (product.cartQuantity ?? 0) + 1;
      cartCountNotifier.value = cartCountNotifier.value + 1;
      setState(() {});
    }).catchError((e) {
      print(e);
      appStore.setLoading(false);
    }).whenComplete(() {
      appStore.setLoading(false);
    });
  }

  /* Future<void> deleteUserGraphs(String? id) async {
    Map req = {
      "id": id,
    };
    await deleteProgressApi(req).then((value) {
      toast(value.message);
      setState(() {});
    }).catchError((e) {
      print(e.toString());
    });
  }*/

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
  }

  Widget mHeading(String? title, {bool? isSeeAll = false, Function? onCall}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title ?? '', style: boldTextStyle(size: 18)).paddingSymmetric(horizontal: 16),
        IconButton(
            splashColor: Colors.transparent,
            highlightColor: Colors.transparent,
            icon: Icon(Feather.chevron_right, color: primaryColor),
            onPressed: () {
              onCall!.call();
            }).paddingRight(2),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: PreferredSize(
        preferredSize: Size.fromHeight(appStore.selectedLanguageCode == 'ar' ? 85 : 70),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Observer(builder: (context) {
                  return Container(
                          decoration: boxDecorationWithRoundedCorners(boxShape: BoxShape.circle, border: Border.all(color: primaryColor, width: 1)),
                          child: cachedImage(userStore.profileImage.validate(), width: 42, height: 42, fit: BoxFit.cover).cornerRadiusWithClipRRect(100).paddingAll(1))
                      .onTap(() {
                    EditProfileScreen().launch(context);
                  });
                }),
                10.width,
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(languages.lblHey + userStore.fName.validate().capitalizeFirstLetter() + " " + userStore.lName.capitalizeFirstLetter() + "👋",
                        style: boldTextStyle(size: 18), overflow: TextOverflow.ellipsis, maxLines: 2),
                    appStore.selectedLanguageCode == 'ar ' ? 0.height : 2.height,
                    Text(languages.lblHomeWelMsg, style: secondaryTextStyle()),
                  ],
                ).expand(),
              ],
            ).expand(),
            Row(
              children: [
                ValueListenableBuilder<int>(
                  valueListenable: cartCountNotifier,
                  builder: (context, count, _) {
                    return Stack(
                      clipBehavior: Clip.none,
                      children: [
                        Container(
                          decoration: boxDecorationWithRoundedCorners(
                              borderRadius: radius(16),
                              border: Border.all(
                                  color: appStore.isDarkMode
                                      ? Colors.white
                                      : context.dividerColor.withOpacity(0.9),
                                  width: 0.6),
                              backgroundColor: appStore.isDarkMode
                                  ? context.scaffoldBackgroundColor
                                  : Colors.white),
                          padding: EdgeInsets.all(10),
                          child: Icon(Icons.shopping_cart_outlined,
                              size: 20,
                              color: appStore.isDarkMode
                                  ? Colors.white
                                  : Colors.grey),
                        ).onTap(() {
                          CartScreen().launch(context);
                        }),
                        if (count > 0)
                          Positioned(
                            right: -6,
                            top: -6,
                            child: Container(
                              padding:
                                  EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: boxDecorationWithRoundedCorners(
                                borderRadius: radius(10),
                                backgroundColor: primaryColor,
                              ),
                              child: Text(
                                count > 99 ? '99+' : count.toString(),
                                style:
                                    secondaryTextStyle(color: Colors.white, size: 10),
                              ),
                            ),
                          ),
                      ],
                    );
                  },
                ),
                12.width,
                Container(
                  decoration: boxDecorationWithRoundedCorners(
                      borderRadius: radius(16),
                      border: Border.all(
                          color: appStore.isDarkMode
                              ? Colors.white
                              : context.dividerColor.withOpacity(0.9),
                          width: 0.6),
                      backgroundColor: appStore.isDarkMode
                          ? context.scaffoldBackgroundColor
                          : Colors.white),
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                  child: Image.asset(ic_notification,
                      width: 24,
                      height: 24,
                      color: appStore.isDarkMode ? Colors.white : Colors.grey),
                ).onTap(
                  () {
                    NotificationScreen().launch(context);
                  },
                ),
              ],
            )
          ],
        ).paddingOnly(top: context.statusBarHeight + 16, left: 16, right: 16, bottom: 6),
      ),
      body: RefreshIndicator(
        backgroundColor: context.scaffoldBackgroundColor,
        onRefresh: () {
          return Future.delayed(
            Duration(seconds: 1),
            () {
              setState(() {});
            },
          );
        },
        child: FutureBuilder(
          future: getDashboardApi(),
          builder: (context, snapshot) {
            if (snapshot.hasData) {
              DashboardResponse? mDashboardResponse = snapshot.data;
              return SingleChildScrollView(
                physics: BouncingScrollPhysics(),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Column(
                      children: [
                        5.height,
                        SizedBox(
                          width: MediaQuery.of(context).size.width,
                          height: 50,
                          child: Marquee(
                            text: 'Enter your height, weight, gender and age to access advanced features.',
                            style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.red),
                            scrollAxis: Axis.horizontal,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            blankSpace: 20.0,
                            velocity: 50.0,
                            pauseAfterRound: const Duration(seconds: 1),
                          ),
                        ).onTap(
                          () {
                            EditProfileScreen().launch(context);
                          },
                        )
                      ],
                    ).visible(userStore.weight.isEmptyOrNull),
                    16.height.visible(!userStore.weight.isEmptyOrNull),
                    AppTextField(
                      controller: mSearchCont,
                      textFieldType: TextFieldType.OTHER,
                      isValidationRequired: false,
                      autoFocus: false,
                      suffix: _getClearButton(),
                      decoration: defaultInputDecoration(context, label: languages.lblSearch, isFocusTExtField: true),
                      onTap: () {
                        hideKeyboard(context);
                        SearchScreen().launch(context);
                      },
                    ).paddingSymmetric(horizontal: 16),
                    16.height,
                    if (mDashboardResponse?.featuredProducts?.isNotEmpty ?? false)
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          mHeading(languages.lblSelectedProductsForYou, onCall: () {
                            ProductScreen().launch(context);
                          }),
                          SizedBox(
                            height: 250,
                            child: ListView.separated(
                              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              scrollDirection: Axis.horizontal,
                              physics: BouncingScrollPhysics(),
                              itemCount: mDashboardResponse!.featuredProducts!.length,
                              separatorBuilder: (context, index) => 16.width,
                              itemBuilder: (context, index) {
                                ProductModel product = mDashboardResponse.featuredProducts![index];
                                return SizedBox(
                                  width: context.width() * 0.58,
                                  child: ProductComponent(
                                    mProductModel: product,
                                    showActions: true,
                                    onAddToCart: (prod) => _addProductToCart(prod),
                                    onToggleFavourite: (prod) => _toggleProductFavourite(prod),
                                    onCall: () {
                                      setState(() {});
                                    },
                                  ),
                                );
                              },
                            ),
                          ),
                          16.height,
                        ],
                      ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        mHeading(languages.lblBodyPartExercise, onCall: () {
                          ViewBodyPartScreen().launch(context);
                        }),
                        HorizontalList(
                          physics: BouncingScrollPhysics(),
                          controller: mScrollController,
                          itemCount: mDashboardResponse!.bodypart!.length,
                          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          spacing: 16,
                          itemBuilder: (context, index) {
                            return BodyPartComponent(bodyPartModel: mDashboardResponse.bodypart![index]);
                          },
                        ),
                      ],
                    ).visible(mDashboardResponse.bodypart!.isNotEmpty),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        10.height,
                        mHeading(languages.lblEquipmentsExercise, onCall: () {
                          ViewEquipmentScreen().launch(context);
                        }),
                        HorizontalList(
                          physics: BouncingScrollPhysics(),
                          itemCount: mDashboardResponse.equipment!.length,
                          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          spacing: 16,
                          itemBuilder: (context, index) {
                            return EquipmentComponent(mEquipmentModel: mDashboardResponse.equipment![index]);
                          },
                        ),
                      ],
                    ).visible(mDashboardResponse.equipment!.isNotEmpty),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        10.height,
                        mHeading(languages.lblWorkouts, onCall: () {
                          FilterWorkoutScreen().launch(context).then((value) {
                            setState(() {});
                          });
                        }),
                        HorizontalList(
                          physics: BouncingScrollPhysics(),
                          itemCount: mDashboardResponse.workout!.length,
                          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          spacing: 16,
                          itemBuilder: (context, index) {
                            return WorkoutComponent(
                              mWorkoutModel: mDashboardResponse.workout![index],
                              onCall: () {
                                appStore.setLoading(true);
                                setState(() {});
                                appStore.setLoading(false);
                              },
                            );
                          },
                        ),
                      ],
                    ).visible(mDashboardResponse.workout!.isNotEmpty),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        10.height,
                        mHeading(languages.lblLevels, onCall: () {
                          ViewLevelScreen().launch(context);
                        }),
                        ListView.builder(
                          shrinkWrap: true,
                          physics: NeverScrollableScrollPhysics(),
                          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          itemCount: mDashboardResponse.level!.length,
                          itemBuilder: (context, index) {
                            return LevelComponent(mLevelModel: mDashboardResponse.level![index]);
                          },
                        ),
                        16.height,
                      ],
                    ).visible(mDashboardResponse.level!.isNotEmpty)
                  ],
                ),
              );
            }
            return snapWidgetHelper(snapshot, loadingWidget: Container(height: mq.height, width: mq.width, color: Colors.transparent, child: Loader()));
          },
        ),
      ),
    );
  }

  Widget _getClearButton() {
    if (!_showClearButton) {
      return mSuffixTextFieldIconWidget(ic_search);
    }

    return IconButton(
      onPressed: () => mSearchCont.clear(),
      icon: Icon(Icons.clear),
    );
  }
}
