import 'dart:async';
import 'dart:ui';

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
import '../components/manual_workout_history_table.dart';
import '../extensions/app_text_field.dart';
import '../extensions/common.dart';
import '../extensions/horizontal_list.dart';
import '../extensions/loader_widget.dart';
import '../extensions/text_styles.dart';
import '../models/dashboard_response.dart';
import '../models/body_part_response.dart';
import '../models/equipment_response.dart';
import '../models/workout_detail_response.dart';
import '../models/level_response.dart';
import '../models/product_response.dart';
import '../models/product_category_response.dart';
import '../models/banner_model.dart';
import '../models/success_story_model.dart';
import '../models/manual_exercise_response.dart';
import '../network/rest_api.dart';
import '../screens/edit_profile_screen.dart';
import '../screens/search_screen.dart';
import '../screens/product_screen.dart';
import '../screens/view_product_category_screen.dart';
import '../screens/view_all_product_screen.dart';
import '../screens/cart_screen.dart';
import '../utils/app_common.dart';
import '../utils/app_images.dart';
import 'filter_workout_screen.dart';
import 'notification_screen.dart';
import '../components/product_component.dart';
import '../components/product_banner_carousel.dart';
import '../components/success_story_slider.dart';
import '../extensions/no_data_widget.dart';
import 'clinic/my_bookings_screen.dart';
import 'clinic/new_booking_screen.dart';
import '../models/clinic_models.dart';

bool? isFirstTimeGraph = false;

class HomeScreen extends StatefulWidget {
  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  ScrollController mScrollController = ScrollController();
  TextEditingController mSearchCont = TextEditingController();
  String? mSearchValue = "";
  bool _showClearButton = false;
  List<ProductCategoryModel> _productCategories = [];
  List<ProductModel> _exclusiveDiscountProducts = [];
  bool _isLoadingCategories = false;
  bool _isLoadingDiscounts = false;
  bool _isBookingMenuOpen = false;

  @override
  void initState() {
    Future.delayed(Duration.zero).then((val) {
      getUserDetailsApiCall();
      if (isFirstTimeGraph == false) {
        graphGet();
      }
      _loadNotificationCount();
    });

    super.initState();

    _fetchProductCategories();
    _fetchDiscountedProducts();
  }


  getUserDetailsApiCall() async {
    await getUSerDetail(context, userStore.userId).whenComplete(() {});
  }

  Future<void> _loadNotificationCount() async {
    if (!userStore.isLoggedIn) return;

    await notificationApi().then((value) {
      notificationCountNotifier.value = value.allUnreadCount ?? 0;
    }).catchError((e) {});
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

  Future<void> _fetchProductCategories() async {
    if (_isLoadingCategories) return;
    _isLoadingCategories = true;
    try {
      final response = await getProductCategoryApi();
      final categories = response.data ?? [];
      if (!mounted) {
        _isLoadingCategories = false;
        return;
      }
      setState(() {
        _productCategories = categories;
        _isLoadingCategories = false;
      });
    } catch (e) {
      print(e);
      if (!mounted) {
        _isLoadingCategories = false;
        return;
      }
      setState(() {
        _isLoadingCategories = false;
      });
    }
  }

  bool _isProductDiscounted(ProductModel product) {
    final bool hasActiveFlag = product.discountActive == true;
    final bool hasDiscountPrice = (product.discountPrice ?? 0) > 0;
    final bool hasDiscountPercent = (product.discountPercent ?? 0) > 0;
    final bool hasLowerFinalPrice =
        product.price != null && product.finalPrice != null && product.finalPrice! < product.price!;
    return hasActiveFlag || hasDiscountPrice || hasDiscountPercent || hasLowerFinalPrice;
  }

  Future<void> _fetchDiscountedProducts() async {
    if (_isLoadingDiscounts) return;
    _isLoadingDiscounts = true;
    try {
      final response = await getProductApi();
      final products = response.data ?? [];
      final Map<int?, ProductModel> uniqueProducts = {};
      for (final product in products) {
        if (_isProductDiscounted(product)) {
          uniqueProducts[product.id] = product;
        }
      }
      if (!mounted) {
        _isLoadingDiscounts = false;
        return;
      }
      setState(() {
        _exclusiveDiscountProducts = uniqueProducts.values.toList();
        _isLoadingDiscounts = false;
      });
    } catch (e) {
      print(e);
      if (!mounted) {
        _isLoadingDiscounts = false;
        return;
      }
      setState(() {
        _isLoadingDiscounts = false;
      });
    }
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

  Widget _buildBookingButton() {
    final label = appStore.selectedLanguageCode == 'ar' ? 'حجز' : 'Book';
    final myBookingsLabel = appStore.selectedLanguageCode == 'ar' ? 'حجوزاتى' : 'My bookings';
    final newBookingLabel = appStore.selectedLanguageCode == 'ar' ? 'حجز جديد' : 'New booking';
    final freeLabel = appStore.selectedLanguageCode == 'ar' ? 'حجز مجاني' : 'Free request';
    final closeLabel = appStore.selectedLanguageCode == 'ar' ? 'إغلاق' : 'Close';
    final freeUsedLabel = appStore.selectedLanguageCode == 'ar'
        ? 'تم استخدام الحجز المجاني بالفعل.'
        : 'Free booking already used.';
    final hasSpecialist = (userStore.assignedSpecialistId ?? 0) > 0;
    final freeUsed = userStore.freeBookingUsedAt.isNotEmpty;

    final List<Widget> menuItems = [];

    if (_isBookingMenuOpen) {
      menuItems.addAll([
        _buildCircularMenuItem(
          icon: Icons.close,
          label: closeLabel,
          backgroundColor: Colors.white,
          iconColor: primaryColor,
          onTap: () async {
            _closeBookingMenu();
          },
        ),
        const SizedBox(height: 12),
        _buildCircularMenuItem(
          icon: Icons.event_note,
          label: myBookingsLabel,
          onTap: () async {
            await _openMyBookings();
          },
        ),
        const SizedBox(height: 12),
        if (hasSpecialist)
          _buildCircularMenuItem(
            icon: Icons.add_circle_outline,
            label: newBookingLabel,
            onTap: () async {
              await _openNewBooking();
            },
          )
        else
          _buildCircularMenuItem(
            icon: Icons.phone_outlined,
            label: freeLabel,
            enabled: !freeUsed,
            disabledMessage: freeUsedLabel,
            onTap: () async {
              await _handleFreeBookingRequest();
            },
          ),
      ]);
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (_isBookingMenuOpen) ...menuItems,
        if (_isBookingMenuOpen) const SizedBox(height: 16),
        GestureDetector(
          onTap: _toggleBookingMenu,
          child: Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: primaryColor,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(color: primaryColor.withOpacity(0.3), blurRadius: 18, offset: const Offset(0, 8)),
              ],
            ),
            alignment: Alignment.center,
            child: const Icon(Icons.calendar_today, color: Colors.white, size: 22),
          ),
        ),
        const SizedBox(height: 8),
        Text(label, style: boldTextStyle(color: primaryColor, size: 12)),
      ],
    );
  }

  Widget _buildCircularMenuItem({
    required IconData icon,
    required String label,
    Future<void> Function()? onTap,
    bool enabled = true,
    Color? backgroundColor,
    Color? iconColor,
    String? disabledMessage,
  }) {
    final Color resolvedBackground = backgroundColor ?? primaryColor;
    final Color resolvedIconColor = iconColor ?? Colors.white;

    return GestureDetector(
      onTap: () async {
        if (enabled) {
          if (onTap != null) await onTap();
        } else if (disabledMessage.validate().isNotEmpty) {
          toast(disabledMessage);
        }
      },
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: enabled ? resolvedBackground : Colors.grey.shade300,
              shape: BoxShape.circle,
              boxShadow: const [
                BoxShadow(color: Colors.black12, blurRadius: 10, offset: Offset(0, 6)),
              ],
            ),
            alignment: Alignment.center,
            child: Icon(icon, color: enabled ? resolvedIconColor : Colors.grey, size: 24),
          ),
          const SizedBox(height: 6),
          Text(
            label,
            style: secondaryTextStyle(size: 12, color: enabled ? null : Colors.grey),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  void _toggleBookingMenu() {
    setState(() {
      _isBookingMenuOpen = !_isBookingMenuOpen;
    });
  }

  void _closeBookingMenu() {
    if (_isBookingMenuOpen) {
      setState(() {
        _isBookingMenuOpen = false;
      });
    }
  }

  Future<void> _openMyBookings() async {
    _closeBookingMenu();
    await MyBookingsScreen().launch(context);
  }

  Future<void> _openNewBooking() async {
    _closeBookingMenu();
    if ((userStore.assignedSpecialistId ?? 0) == 0) {
      toast(appStore.selectedLanguageCode == 'ar'
          ? 'لا يوجد أخصائي مرتبط بحسابك.'
          : 'No specialist assigned to your account.');
      return;
    }
    final refreshed = await NewBookingScreen().launch(context);
    if (refreshed == true) {
      await getUSerDetail(context, userStore.userId);
      setState(() {});
    }
  }

  Future<void> _handleFreeBookingRequest() async {
    _closeBookingMenu();
    await _showFreeBookingDialog();
  }

  Future<void> _showFreeBookingDialog() async {
    try {
      appStore.setLoading(true);
      final branches = await fetchClinicBranches();
      appStore.setLoading(false);
      if (branches.isEmpty) {
        toast(appStore.selectedLanguageCode == 'ar' ? 'لا توجد فروع متاحة حالياً.' : 'No branches available.');
        return;
      }
      int? selectedBranch = branches.first.id;
      final controller = TextEditingController(text: userStore.phoneNo);
      bool isSubmitting = false;
      await showDialog(
        context: context,
        builder: (ctx) {
          return StatefulBuilder(builder: (context, setStateDialog) {
            return AlertDialog(
              title: Text(appStore.selectedLanguageCode == 'ar' ? 'طلب حجز مجاني' : 'Free booking request'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    DropdownButtonFormField<int>(
                      value: selectedBranch,
                      items: branches
                          .map((e) => DropdownMenuItem<int>(value: e.id, child: Text(e.name)))
                          .toList(),
                      onChanged: (value) {
                        setStateDialog(() {
                          selectedBranch = value;
                        });
                      },
                      decoration: InputDecoration(
                        labelText: appStore.selectedLanguageCode == 'ar' ? 'اختر الفرع' : 'Select branch',
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: controller,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        labelText: appStore.selectedLanguageCode == 'ar' ? 'رقم الهاتف' : languages.lblPhoneNumber,
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () {
                    Navigator.of(ctx).pop();
                  },
                  child: Text(languages.lblCancel),
                ),
                ElevatedButton(
                  onPressed: isSubmitting
                      ? null
                      : () async {
                          if ((selectedBranch ?? 0) == 0) {
                            toast(appStore.selectedLanguageCode == 'ar' ? 'يرجى اختيار الفرع.' : 'Please select a branch.');
                            return;
                          }
                          if (controller.text.trim().isEmpty) {
                            toast(appStore.selectedLanguageCode == 'ar' ? 'يرجى إدخال رقم الهاتف.' : 'Please enter phone number.');
                            return;
                          }
                          setStateDialog(() {
                            isSubmitting = true;
                          });
                          try {
                            await submitFreeBookingRequest({
                              'branch_id': selectedBranch,
                              'phone': controller.text.trim(),
                            });
                            await userStore.setFreeBookingUsedAt(DateTime.now().toIso8601String());
                            toast(appStore.selectedLanguageCode == 'ar'
                                ? 'تم إرسال الطلب وسيتم التواصل معك قريباً'
                                : 'Request sent. We will contact you soon.');
                            Navigator.of(ctx).pop();
                            setState(() {});
                          } catch (e) {
                            toast(e.toString());
                          } finally {
                            setStateDialog(() {
                              isSubmitting = false;
                            });
                          }
                        },
                  style: ElevatedButton.styleFrom(backgroundColor: primaryColor),
                  child: isSubmitting
                      ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Text(appStore.selectedLanguageCode == 'ar' ? 'إرسال' : languages.lblSubmit,
                          style: boldTextStyle(color: Colors.white)),
                ),
              ],
            );
          });
        },
      );
    } catch (e) {
      appStore.setLoading(false);
      toast(e.toString());
    }
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
                ValueListenableBuilder<int>(
                  valueListenable: notificationCountNotifier,
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
                          padding:
                              EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                          child: Image.asset(ic_notification,
                              width: 24,
                              height: 24,
                              color:
                                  appStore.isDarkMode ? Colors.white : Colors.grey),
                        ).onTap(() {
                          NotificationScreen().launch(context);
                        }),
                        if (count > 0)
                          Positioned(
                            right: -6,
                            top: -6,
                            child: Container(
                              padding: EdgeInsets.symmetric(
                                  horizontal: 6, vertical: 2),
                              decoration: boxDecorationWithRoundedCorners(
                                borderRadius: radius(10),
                                backgroundColor: Colors.redAccent,
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
              ],
            )
          ],
        ).paddingOnly(top: context.statusBarHeight + 16, left: 16, right: 16, bottom: 6),
      ),
      body: RefreshIndicator(
        backgroundColor: context.scaffoldBackgroundColor,
        onRefresh: () async {
          await Future.wait([
            _fetchProductCategories(),
            _fetchDiscountedProducts(),
          ]);
          setState(() {});
        },
        child: Stack(
          fit: StackFit.expand,
          children: [
            FutureBuilder(
              future: getDashboardApi(),
              builder: (context, snapshot) {
                if (snapshot.hasData) {
                  DashboardResponse? mDashboardResponse = snapshot.data;
                  List<ProductModel> featuredProducts = mDashboardResponse?.featuredProducts ?? <ProductModel>[];
                  List<BannerModel> productBanners = mDashboardResponse?.productBanners ?? <BannerModel>[];
                  List<SuccessStoryModel> successStories = mDashboardResponse?.successStories ?? <SuccessStoryModel>[];
                  List<ManualExerciseModel> manualExercises = mDashboardResponse?.manualExercises ?? <ManualExerciseModel>[];
                  List<BodyPartModel> bodyParts = mDashboardResponse?.bodypart ?? <BodyPartModel>[];
                  List<EquipmentModel> equipments = mDashboardResponse?.equipment ?? <EquipmentModel>[];
                  List<WorkoutDetailModel> workouts = mDashboardResponse?.workout ?? <WorkoutDetailModel>[];
                  List<LevelModel> levels = mDashboardResponse?.level ?? <LevelModel>[];

                  bool hasFeaturedProducts = featuredProducts.isNotEmpty;
                  bool hasProductBanners = productBanners.isNotEmpty;
                  bool hasSuccessStories = successStories.isNotEmpty;
                  bool hasManualExercises = manualExercises.isNotEmpty;
                  bool hasBodyParts = bodyParts.isNotEmpty;
                  bool hasEquipments = equipments.isNotEmpty;
                  bool hasWorkouts = workouts.isNotEmpty;
                  bool hasLevels = levels.isNotEmpty;
                  bool hasProductCategories = _productCategories.isNotEmpty;
                  bool hasExclusiveDiscounts = _exclusiveDiscountProducts.isNotEmpty;
                  bool hasAnyDashboardContent =
                      hasFeaturedProducts || hasProductCategories || hasExclusiveDiscounts || hasBodyParts || hasEquipments || hasWorkouts || hasLevels || hasProductBanners || hasSuccessStories;

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
                        ManualWorkoutHistoryTable(
                          entries: manualExercises,
                          title: appStore.selectedLanguageCode == 'ar'
                              ? 'سجل التمارين اليدوي'
                              : 'Manual Workout History',
                        ),
                        if (hasProductBanners)
                          ProductBannerCarousel(banners: productBanners).paddingBottom(16),
                        if (!hasAnyDashboardContent)
                          Padding(
                            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 32),
                            child: NoDataWidget(
                              image: no_data_found,
                              title: languages.lblResultNoFound,
                            ),
                          ),
                        if (hasFeaturedProducts)
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
                                  itemCount: featuredProducts.length,
                                  separatorBuilder: (context, index) => 16.width,
                                  itemBuilder: (context, index) {
                                    ProductModel product = featuredProducts[index];
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
                        if (hasProductCategories)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              mHeading(languages.lblProductCategory, onCall: () {
                                ViewProductCategoryScreen().launch(context);
                              }),
                              SizedBox(
                                height: 136,
                                child: ListView.separated(
                                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                                  scrollDirection: Axis.horizontal,
                                  physics: BouncingScrollPhysics(),
                                  itemCount: _productCategories.length,
                                  separatorBuilder: (_, __) => 16.width,
                                  itemBuilder: (context, index) {
                                    return _buildCategoryItem(_productCategories[index]);
                                  },
                                ),
                              ),
                              16.height,
                            ],
                          ),
                        if (hasExclusiveDiscounts)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              mHeading(languages.lblExclusiveDiscounts, onCall: () {
                                ViewAllProductScreen(
                                  title: languages.lblExclusiveDiscounts,
                                  showDiscountOnly: true,
                                ).launch(context);
                              }),
                              SizedBox(
                                height: 250,
                                child: ListView.separated(
                                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                  scrollDirection: Axis.horizontal,
                                  physics: BouncingScrollPhysics(),
                                  itemCount: _exclusiveDiscountProducts.length,
                                  separatorBuilder: (context, index) => 16.width,
                                  itemBuilder: (context, index) {
                                    ProductModel product = _exclusiveDiscountProducts[index];
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
                        if (hasBodyParts)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              mHeading(languages.lblBodyPartExercise, onCall: () {
                                ViewBodyPartScreen().launch(context);
                              }),
                              HorizontalList(
                                physics: BouncingScrollPhysics(),
                                controller: mScrollController,
                                itemCount: bodyParts.length,
                                padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                spacing: 16,
                                itemBuilder: (context, index) {
                                  return BodyPartComponent(bodyPartModel: bodyParts[index]);
                                },
                              ),
                            ],
                          ),
                        if (hasEquipments)
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              10.height,
                              mHeading(languages.lblEquipmentsExercise, onCall: () {
                                ViewEquipmentScreen().launch(context);
                              }),
                              HorizontalList(
                                physics: BouncingScrollPhysics(),
                                itemCount: equipments.length,
                                padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                spacing: 16,
                                itemBuilder: (context, index) {
                                  return EquipmentComponent(mEquipmentModel: equipments[index]);
                                },
                              ),
                            ],
                          ),
                        if (hasWorkouts)
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
                                itemCount: workouts.length,
                                padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                spacing: 16,
                                itemBuilder: (context, index) {
                                  return WorkoutComponent(
                                    mWorkoutModel: workouts[index],
                                    onCall: () {
                                      appStore.setLoading(true);
                                      setState(() {});
                                      appStore.setLoading(false);
                                    },
                                  );
                                },
                              ),
                            ],
                          ),
                        if (hasLevels)
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
                                itemCount: levels.length,
                                itemBuilder: (context, index) {
                                  return LevelComponent(mLevelModel: levels[index]);
                                },
                              ),
                              16.height,
                            ],
                          ),
                        if (hasSuccessStories)
                          SuccessStorySlider(stories: successStories)
                              .paddingSymmetric(horizontal: 16, vertical: 24),
                      ],
                    ),
                  );
                }
                return snapWidgetHelper(snapshot,
                    loadingWidget: Container(height: mq.height, width: mq.width, color: Colors.transparent, child: Loader()));
              },
            ),
            SafeArea(
              top: false,
              right: false,
              child: Align(
                alignment: Alignment.bottomLeft,
                child: Padding(
                  padding: const EdgeInsets.only(left: 24, bottom: 30),
                  child: _buildBookingButton(),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildDashboardSections({
    required BuildContext context,
    required DashboardResponse? dashboardResponse,
  }) {
    List<Widget> sections = [];
    List<ProductModel> featuredProducts = dashboardResponse?.featuredProducts ?? <ProductModel>[];
    List<BodyPartModel> bodyParts = dashboardResponse?.bodypart ?? <BodyPartModel>[];
    List<EquipmentModel> equipments = dashboardResponse?.equipment ?? <EquipmentModel>[];
    List<WorkoutDetailModel> workouts = dashboardResponse?.workout ?? <WorkoutDetailModel>[];
    List<LevelModel> levels = dashboardResponse?.level ?? <LevelModel>[];

    bool hasFeaturedProducts = featuredProducts.isNotEmpty;
    bool hasProductCategories = _productCategories.isNotEmpty;
    bool hasExclusiveDiscounts = _exclusiveDiscountProducts.isNotEmpty;
    bool hasBodyParts = bodyParts.isNotEmpty;
    bool hasEquipments = equipments.isNotEmpty;
    bool hasWorkouts = workouts.isNotEmpty;
    bool hasLevels = levels.isNotEmpty;

    if (hasFeaturedProducts)
      sections.add(
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
                itemCount: featuredProducts.length,
                separatorBuilder: (context, index) => 16.width,
                itemBuilder: (context, index) {
                  ProductModel product = featuredProducts[index];
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
      );
    if (hasProductCategories)
      sections.add(_buildProductCategorySection(context));
    if (hasExclusiveDiscounts)
      sections.add(_buildExclusiveDiscountSection(context));
    if (hasBodyParts)
      sections.add(
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            mHeading(languages.lblBodyPartExercise, onCall: () {
              ViewBodyPartScreen().launch(context);
            }),
            HorizontalList(
              physics: BouncingScrollPhysics(),
              controller: mScrollController,
              itemCount: bodyParts.length,
              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              spacing: 16,
              itemBuilder: (context, index) {
                return BodyPartComponent(bodyPartModel: bodyParts[index]);
              },
            ),
          ],
        ),
      );
    if (hasEquipments)
      sections.add(
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            10.height,
            mHeading(languages.lblEquipmentsExercise, onCall: () {
              ViewEquipmentScreen().launch(context);
            }),
            HorizontalList(
              physics: BouncingScrollPhysics(),
              itemCount: equipments.length,
              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              spacing: 16,
              itemBuilder: (context, index) {
                return EquipmentComponent(mEquipmentModel: equipments[index]);
              },
            ),
          ],
        ),
      );
    if (hasWorkouts)
      sections.add(
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
              itemCount: workouts.length,
              padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              spacing: 16,
              itemBuilder: (context, index) {
                return WorkoutComponent(
                  mWorkoutModel: workouts[index],
                  onCall: () {
                    appStore.setLoading(true);
                    setState(() {});
                    appStore.setLoading(false);
                  },
                );
              },
              ),
          ],
        ),
      );
    if (hasLevels)
      sections.add(
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
              itemCount: levels.length,
              itemBuilder: (context, index) {
                return LevelComponent(mLevelModel: levels[index]);
              },
            ),
            16.height,
          ],
        ),
      );
    if (sections.isEmpty)
      sections.add(
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 32),
          child: SizedBox(
            width: double.infinity,
            height: context.height() * 0.45,
            child: NoDataWidget(
              image: no_data_found,
              title: languages.lblResultNoFound,
              subTitle: languages.lblNoFoundData,
            ),
          ),
        ),
      );

    return sections;
  }

  Widget _buildCategoryItem(ProductCategoryModel category) {
    return SizedBox(
      width: 92,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: appStore.isDarkMode ? context.cardColor : GreyLightColor,
            ),
            alignment: Alignment.center,
            child: ClipOval(
              child: cachedImage(
                category.productcategoryImage.validate(),
                height: 74,
                width: 74,
                fit: BoxFit.cover,
              ),
            ),
          ),
          8.height,
          Text(
            category.title.validate(),
            style: primaryTextStyle(size: 13),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
          ),
        ],
      ),
    ).onTap(() {
      ViewAllProductScreen(
        isCategory: true,
        title: category.title.validate(),
        id: category.id.validate(),
      ).launch(context);
    });
  }

  Widget _buildProductCategorySection(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        mHeading(languages.lblProductCategory, onCall: () {
          ViewProductCategoryScreen().launch(context);
        }),
        SizedBox(
          height: 136,
          child: ListView.separated(
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            scrollDirection: Axis.horizontal,
            physics: BouncingScrollPhysics(),
            itemCount: _productCategories.length,
            separatorBuilder: (_, __) => 16.width,
            itemBuilder: (context, index) {
              return _buildCategoryItem(_productCategories[index]);
            },
          ),
        ),
        16.height,
      ],
    );
  }

  Widget _buildExclusiveDiscountSection(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        mHeading(languages.lblExclusiveDiscounts, onCall: () {
          ViewAllProductScreen(
            title: languages.lblExclusiveDiscounts,
            showDiscountOnly: true,
          ).launch(context);
        }),
        SizedBox(
          height: 250,
          child: ListView.separated(
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            scrollDirection: Axis.horizontal,
            physics: BouncingScrollPhysics(),
            itemCount: _exclusiveDiscountProducts.length,
            separatorBuilder: (context, index) => 16.width,
            itemBuilder: (context, index) {
              ProductModel product = _exclusiveDiscountProducts[index];
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
