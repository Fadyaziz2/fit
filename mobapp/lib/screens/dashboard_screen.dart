import 'dart:io';
import 'dart:ui';

import 'package:crisp_chat/crisp_chat.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:mighty_fitness/components/CircularButton.dart';
import 'package:mighty_fitness/models/app_setting_response.dart';
import 'package:mighty_fitness/models/question_answer_model.dart';
import 'package:mighty_fitness/pages/home/game_home_screen.dart';
import 'package:mighty_fitness/screens/Schedule_Screen.dart';
import 'package:mighty_fitness/screens/chatting_image_screen.dart';
import 'package:mighty_fitness/screens/live_chat_screen.dart';
import 'package:mighty_fitness/screens/main_goal_screen.dart';
import 'package:mighty_fitness/service/VersionServices.dart';
import 'package:mighty_fitness/utils/_storeFirstTimeOpen.dart';

import '../../extensions/extension_util/context_extensions.dart';
import '../../screens/diet_screen.dart';
import '../components/double_back_to_close_app.dart';
import '../components/permission.dart';
import '../extensions/LiveStream.dart';
import '../extensions/colors.dart';
import '../extensions/constants.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../extensions/shared_pref.dart';
import '../extensions/text_styles.dart';
import '../main.dart';
import '../models/bottom_bar_item_model.dart';
import '../network/rest_api.dart';
import '../screens/product_screen.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';
import '../utils/app_constants.dart';
import '../utils/app_images.dart';
import 'home_screen.dart';
import 'profile_screen.dart';
import 'progress_screen.dart';

bool? isFirstTime = false;
AppVersion? app_update_check = null;

class DashboardScreen extends StatefulWidget {
  @override
  _DashboardScreenState createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> with SingleTickerProviderStateMixin {
  int mCurrentIndex = 0;
  int mCounter = 0;
  late CrispConfig configData;

  late AnimationController _controller;
  late Animation<double> _animation;
  bool _isExpanded = false;

  final tab = [
    HomeScreen(),
    DietScreen(),
    ProductScreen(),
    ProgressScreen(),
    ScheduleScreen(),
    ProfileScreen(),
  ];

  List<BottomBarItemModel> bottomItemList = [
    BottomBarItemModel(iconData: ic_home_outline, selectedIconData: ic_home_fill, labelText: languages.lblHome),
    BottomBarItemModel(iconData: ic_diet_outline, selectedIconData: ic_diet_fill, labelText: languages.lblDiet),
    BottomBarItemModel(iconData: ic_store_outline, selectedIconData: ic_store_fill, labelText: languages.lblShop),
    BottomBarItemModel(iconData: ic_report_outline, selectedIconData: ic_report_fill, labelText: languages.lblReport),
    BottomBarItemModel(iconData: ic_schedule, selectedIconData: ic_fill_schedule, labelText: languages.lblSchedule),
    BottomBarItemModel(iconData: ic_user, selectedIconData: ic_user_fill_icon, labelText: languages.lblProfile),
  ];

  @override
  void initState() {
    super.initState();
    init();
    LiveStream().on("LANGUAGE", (s) {
      setState(() {});
    });
    _controller = AnimationController(
      duration: const Duration(milliseconds: 300),
      vsync: this,
    );
    _animation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeInOut,
    );
  }

  init() async {
    //
    PlatformDispatcher.instance.onPlatformBrightnessChanged = () {
      if (getIntAsync(THEME_MODE_INDEX) == ThemeModeSystem) {
        appStore.setDarkMode(MediaQuery.of(context).platformBrightness == Brightness.light);
      }
    };
    await getSettingList();
    getFitBotListApiCall();
    Permissions.activityPermissionsGranted();

    LiveStream().on(CHANGE_LANGUAGE, (p0) {
      setState(() {});
    });
  }

  Future<void> getSettingList() async {
    await getSettingApi().then((value) {
      print('------------------------111-------${value.currencySetting?.code.validate()}');
      userStore.setCurrencyCodeID(value.currencySetting?.symbol.validate() ?? '');
      userStore.setCurrencyPositionID(value.currencySetting?.position.validate() ?? '');
      userStore.setCurrencyCode(value.currencySetting?.code.validate() ?? '');

      /// Config crispChat

      for (int i = 0; i < value.data!.length; i++) {
        switch (value.data![i].key) {
          case "terms_condition":
            {
              userStore.setTermsCondition(value.data![i].value.validate());
            }
          case "privacy_policy":
            {
              userStore.setPrivacyPolicy(value.data![i].value.validate());
            }
          case "ONESIGNAL_APP_ID":
            {
              userStore.setOneSignalAppID(value.data![i].value.validate());
            }
          case "ONESIGNAL_REST_API_KEY":
            {
              userStore.setOnesignalRestApiKey(value.data![i].value.validate());
            }
          case "ADMOB_BannerId":
            {
              userStore.setAdmobBannerId(value.data![i].value.validate());
            }
          case "ADMOB_InterstitialId":
            {
              userStore.setAdmobInterstitialId(value.data![i].value.validate());
            }
          case "ADMOB_BannerIdIos":
            {
              userStore.setAdmobBannerIdIos(value.data![i].value.validate());
            }
          case "ADMOB_InterstitialIdIos":
            {
              userStore.setAdmobInterstitialIdIos(value.data![i].value.validate());
            }
          case "CHATGPT_API_KEY":
            {
              userStore.setChatGptApiKey(value.data?[i].value.validate() ?? "");
            }
          case "AdsBannerDetail_Show_Ads_On_Diet_Detail":
            {
              userStore.setAdsBannerDetailShowAdsOnDietDetail(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Banner_Ads_OnDiet":
            {
              userStore.setAdsBannerDetailShowBannerAdsOnDiet(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Ads_On_Workout_Detail":
            {
              userStore.setAdsBannerDetailShowAdsOnWorkoutDetail(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Banner_On_Workouts":
            {
              userStore.setAdsBannerDetailShowBannerOnWorkouts(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Ads_On_Exercise_Detail":
            {
              userStore.setAdsBannerDetailShowAdsOnExerciseDetail(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Banner_On_Equipment":
            {
              userStore.setAdsBannerDetailShowBannerOnEquipment(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Ads_On_Product_Detail":
            {
              userStore.setAdsBannerDetailShowAdsOnProductDetail(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Banner_On_Product":
            {
              userStore.setAdsBannerDetailShowBannerOnProduct(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Ads_On_Progress_Detail":
            {
              userStore.setAdsBannerDetailShowAdsOnProgressDetail(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Banner_On_BodyPart":
            {
              userStore.setAdsBannerDetailShowBannerOnBodyPart(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Ads_On_Blog_Detail":
            {
              userStore.setAdsBannerDetailShowAdsOnBlogDetail(value.data![i].value.toInt());
            }
          case "AdsBannerDetail_Show_Banner_On_Level":
            {
              userStore.setAdsBannerDetailShowBannerOnLevel(value.data![i].value.toInt());
            }
          case "subscription_system":
            {
              userStore.setSubscription(value.data![i].value.toString());
            }
        }
      }
      getSettingData().whenComplete(() {
        print("-----------211>>>${getStringAsync(CRISP_CHAT_WEB_SITE_ID)}");
        print("-----------212>>>${getBoolAsync(CRISP_CHAT_ENABLED)}");

        if (getStringAsync(CRISP_CHAT_WEB_SITE_ID) != null && getStringAsync(CRISP_CHAT_WEB_SITE_ID).isNotEmpty) {
          User user = User(email: userStore.email, nickName: "${userStore.displayName}", avatar: userStore.profileImage ?? "");
          configData = CrispConfig(
            user: user,
            tokenId: userStore.userId.toString(),
            enableNotifications: true,
            websiteID: getStringAsync(CRISP_CHAT_WEB_SITE_ID),
          );
        }
        if (app_update_check != null) {
          VersionService().getVersionData(context, app_update_check);
        }
      });
    });
  }

  Future<void> getFitBotListApiCall() async {
    await getFitBotList().then((value) {
      value.data?.reversed.forEach((data) {
        questionAnswers.insert(
            0, QuestionImageAnswerModel(question: data.question, imageUri: "", answer: data.answer != null ? StringBuffer(data.answer ?? '') : null, isLoading: false, smartCompose: ''));
      });
    });
  }

  @override
  void didChangeDependencies() {
    if (getIntAsync(THEME_MODE_INDEX) == ThemeModeSystem) appStore.setDarkMode(MediaQuery.of(context).platformBrightness == Brightness.dark);
    setState(() {});
    super.didChangeDependencies();
  }

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
  }

  void _toggleExpand() {
    setState(() {
      _isExpanded = !_isExpanded;
      if (_isExpanded) {
        _controller.forward();
      } else {
        _controller.reverse();
      }
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          DoubleBackToCloseApp(
            snackBar: SnackBar(
              elevation: 4,
              backgroundColor: appStore.isDarkMode ? cardDarkColor : primaryOpacity,
              content: Text(languages.lblTapBackAgainToLeave, style: primaryTextStyle()),
            ),
            child: AnimatedContainer(color: context.cardColor, duration: const Duration(seconds: 1), child: tab[mCurrentIndex]),
          ),
          if (mCurrentIndex == 0) ...[
            Positioned(
              bottom: 30,
              right: 25,
              child: Column(
                children: [
                  AnimatedBuilder(
                    animation: _animation,
                    builder: (context, child) {
                      return Transform.translate(
                        offset: Offset(0, -30 * _animation.value),
                        child: Visibility(
                          visible: _isExpanded,
                          child: CircularButton(
                            height: 53,
                            width: 53,
                            color: primaryColor,
                            onClick: _isExpanded
                                ? () async {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(builder: (context) => GameHomeScreen()),
                                    );
                                    _toggleExpand();
                                  }
                                : null,
                            image: ic_mental,
                          ),
                        ),
                      );
                    },
                  ),
                  getBoolAsync(CRISP_CHAT_ENABLED)==true?AnimatedBuilder(
                    animation: _animation,
                    builder: (context, child) {
                      return Transform.translate(
                        offset: Offset(0, -20 * _animation.value),
                        child: Visibility(
                          visible: _isExpanded,
                          child: CircularButton(
                            height: 53,
                            width: 53,
                            color: primaryColor,
                            onClick: _isExpanded
                                ? () async {
                                    configureCrispChat();
                                    String? sessionId = await FlutterCrispChat.getSessionIdentifier();
                                    LiveChatScreen().launch(context);
                                    await FlutterCrispChat.openCrispChat(config: configData);
                                    _toggleExpand();
                                  }
                                : null,
                            image: ic_support,
                          ),
                        ),
                      );
                    },
                  ):SizedBox.shrink(),
                  AnimatedBuilder(
                    animation: _animation,
                    builder: (context, child) {
                      return Transform.translate(
                        offset: Offset(0, -10 * _animation.value),
                        child: Visibility(
                          visible: _isExpanded,
                          child: CircularButton(
                            height: 53,
                            width: 53,
                            color: primaryColor,
                            onClick: _isExpanded
                                ? () async {
                                    bool? isFirstTime = await getFirstTimeOpen();
                                    if (isFirstTime == false || isFirstTime == null) {
                                      MainGoalScreen().launch(context);
                                    } else {
                                      ChattingImageScreen().launch(context);
                                    }
                                    _toggleExpand();
                                  }
                                : null,
                            image: ic_bot,
                          ),
                        ),
                      );
                    },
                  ),
                  CircularButton(
                    height: 60,
                    width: 60,
                    onClick: _toggleExpand,
                    color: primaryColor,
                    icon: Icon(_isExpanded ? Icons.close : Icons.menu, color: Colors.white),
                  ),
                ],
              ),
            )
          ],
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        showUnselectedLabels: true,
        showSelectedLabels: true,
        enableFeedback: false,
        selectedLabelStyle: secondaryTextStyle(size: 13),
        unselectedLabelStyle: secondaryTextStyle(size: 12),
        backgroundColor: context.cardColor,
        currentIndex: mCurrentIndex,
        unselectedItemColor: Colors.grey,
        selectedItemColor: primaryColor,
        onTap: (index) {
          mCurrentIndex = index;
          setState(() {});
        },
        items: [
          BottomNavigationBarItem(
              tooltip: languages.lblHome,
              icon: Image.asset(ic_home_outline, color: Colors.grey, height: 24),
              activeIcon: Image.asset(ic_home_fill, color: primaryColor, height: 24),
              label: languages.lblHome),
          BottomNavigationBarItem(
              tooltip: languages.lblDiet,
              icon: Image.asset(ic_diet_outline, color: Colors.grey, height: 24),
              activeIcon: Image.asset(ic_diet_fill, color: primaryColor, height: 24),
              label: languages.lblDiet),
          BottomNavigationBarItem(
              tooltip: languages.lblShop,
              icon: Image.asset(ic_store_outline, color: Colors.grey, height: 24),
              activeIcon: Image.asset(ic_store_fill, color: primaryColor, height: 24),
              label: languages.lblShop),
          BottomNavigationBarItem(
              tooltip: languages.lblReport,
              icon: Image.asset(ic_report_outline, color: Colors.grey, height: 24),
              activeIcon: Image.asset(ic_report_fill, color: primaryColor, height: 24),
              label: languages.lblReport),
          BottomNavigationBarItem(
              tooltip: languages.lblSchedule,
              icon: Image.asset(ic_schedule, color: Colors.grey, height: 22),
              activeIcon: Image.asset(ic_fill_schedule, color: primaryColor, height: 24),
              label: languages.lblSchedule),
          BottomNavigationBarItem(
              tooltip: languages.lblProfile,
              icon: Image.asset(ic_user, color: Colors.grey, height: 24),
              activeIcon: Image.asset(ic_user_fill_icon, color: primaryColor, height: 24),
              label: languages.lblProfile),
        ],
      ),
    );
  }
}

configureCrispChat() async {
  FlutterCrispChat.setSessionString(
    key: userStore.userId.toString(),
    value: userStore.userId.toString(),
  );

  /// Checking session ID After 5 sec
  await Future.delayed(const Duration(seconds: 5), () async {
    String? sessionId = await FlutterCrispChat.getSessionIdentifier();
    if (sessionId != null) {
      if (kDebugMode) {
        print("Session ID::: $sessionId");
      }
    } else {
      if (kDebugMode) {}
    }
  });
}
