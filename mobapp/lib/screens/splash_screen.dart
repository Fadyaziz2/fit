import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mighty_fitness/extensions/constants.dart';
import 'package:mighty_fitness/languageConfiguration/LanguageDataConstant.dart';
import 'package:mighty_fitness/languageConfiguration/ServerLanguageResponse.dart';
import 'package:mighty_fitness/network/rest_api.dart';
import 'package:permission_handler/permission_handler.dart';
import '../../screens/dashboard_screen.dart';
import '../../extensions/extension_util/duration_extensions.dart';
import '../../extensions/extension_util/int_extensions.dart';
import '../../extensions/extension_util/context_extensions.dart';
import '../extensions/shared_pref.dart';
import '../../extensions/extension_util/widget_extensions.dart';
import '../../extensions/colors.dart';
import '../../main.dart';
import '../../utils/app_images.dart';
import '../utils/app_constants.dart';
import 'sign_in_screen.dart';
import 'walk_through_screen.dart';

class SplashScreen extends StatefulWidget {
  static String tag = '/SplashScreen';

  @override
  SplashScreenState createState() => SplashScreenState();
}

class SplashScreenState extends State<SplashScreen> {

  @override
  void initState() {
    super.initState();
    Future.delayed(Duration.zero).then((val) {
      _checkNotifyPermission();
    });
  }

  init() async {
    await 1.seconds.delay;
    if (!getBoolAsync(IS_FIRST_TIME)) {
      WalkThroughScreen().launch(context, isNewTask: true);
    } else {
      if (userStore.isLoggedIn) {
        DashboardScreen().launch(context, isNewTask: true);
      } else {
        SignInScreen().launch(context, isNewTask: true);
      }
    }
  }

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
  }

  void _checkNotifyPermission() async {
    String versionNo =  getStringAsync(CURRENT_LAN_VERSION,defaultValue: LanguageVersion);
    print("---------59>>>${versionNo}");
    await getLanguageList(versionNo).then((value) {
      print("---------61>>>${value.data?.length}");
      appStore.setLoading(false);
      if (value.status == true) {
        setValue(CURRENT_LAN_VERSION, value.currentVersionNo.toString());
        if (value.data!.length > 0) {
          defaultServerLanguageData = value.data;
          performLanguageOperation(defaultServerLanguageData);
          setValue(LanguageJsonDataRes, value.toJson());
          bool isSetLanguage = sharedPreferences.getBool(IS_SELECTED_LANGUAGE_CHANGE)??false;
          if (!isSetLanguage) {
            for (int i = 0; i < value.data!.length; i++) {
              if (value.data![i].isDefaultLanguage == 1) {
                setValue(SELECTED_LANGUAGE_CODE, value.data![i].languageCode);
                setValue(SELECTED_LANGUAGE_COUNTRY_CODE, value.data![i].countryCode);
                appStore.setLanguage(value.data![i].languageCode!, context: context);
                break;
              }
            }
          }
        } else {
          defaultServerLanguageData = [];
          selectedServerLanguageData = null;
          setValue(LanguageJsonDataRes, "");
        }
      } else {
        String getJsonData = getStringAsync(LanguageJsonDataRes)??'';

        if (getJsonData.isNotEmpty) {
          ServerLanguageResponse languageSettings = ServerLanguageResponse.fromJson(json.decode(getJsonData.trim()));
          if (languageSettings.data!.length > 0) {
            defaultServerLanguageData = languageSettings.data;
            performLanguageOperation(defaultServerLanguageData);
          }
        }
      }
    }).catchError((error) {
      appStore.setLoading(false);
     // log(error);
    });
    if (await Permission.notification.isGranted) {
      init();
    } else {
      await Permission.notification.request();
      init();
    }
  }


  @override
  Widget build(BuildContext context) {
    mq=MediaQuery.of(context).size;
    return AnnotatedRegion(
      value: SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: appStore.isDarkMode ? Brightness.light : Brightness.dark,
        systemNavigationBarIconBrightness: appStore.isDarkMode ? Brightness.light : Brightness.dark,
      ),
      child: Scaffold(
        backgroundColor: appStore.isDarkMode ? context.scaffoldBackgroundColor : whiteColor,
        body: Stack(
          alignment: Alignment.bottomCenter,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                45.height,
                Image.asset(ic_splash_logo, width: 150, height: 90, fit: BoxFit.fill),
              ],
            ).center(),
          ],
        ).paddingBottom(10),
      ),
    );
  }
}
