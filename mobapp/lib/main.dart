import 'dart:async';
import 'dart:convert';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_mobx/flutter_mobx.dart';
import 'package:mighty_fitness/Chat/model/file_model.dart';
import 'package:mighty_fitness/languageConfiguration/AppLocalizations.dart';
import 'package:mighty_fitness/languageConfiguration/BaseLanguage.dart';
import 'package:mighty_fitness/languageConfiguration/LanguageDataConstant.dart';
import 'package:mighty_fitness/languageConfiguration/LanguageDefaultJson.dart';
import 'package:mighty_fitness/languageConfiguration/ServerLanguageResponse.dart';
import 'package:mighty_fitness/service/chat_message_service.dart';
import 'package:mighty_fitness/service/notification_service.dart';
import 'package:mighty_fitness/service/user_service.dart';
import '../utils/app_colors.dart';
import '../store/NotificationStore/NotificationStore.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../extensions/extension_util/string_extensions.dart';
import '../../extensions/system_utils.dart';
import '../../store/app_store.dart';
import 'app_theme.dart';
import 'extensions/common.dart';
import 'extensions/constants.dart';
import 'extensions/decorations.dart';
import 'extensions/shared_pref.dart';
import 'models/progress_setting_model.dart';
import 'network/rest_api.dart';
import 'screens/no_internet_screen.dart';
import 'screens/splash_screen.dart';
import 'store/UserStore/UserStore.dart';
import 'utils/app_common.dart';
import 'utils/app_config.dart';
import 'utils/app_constants.dart';
import 'package:awesome_notifications/awesome_notifications.dart';

AppStore appStore = AppStore();
UserStore userStore = UserStore();
ChatMessageService chatMessageService = ChatMessageService();
NotificationStore notificationStore = NotificationStore();
LanguageJsonData? selectedServerLanguageData;
List<LanguageJsonData>? defaultServerLanguageData = [];
late Size mq;
late SharedPreferences sharedPreferences;
final navigatorKey = GlobalKey<NavigatorState>();
late BaseLanguage languages;
UserService userService = UserService();
late List<FileModel> fileList = [];
bool mIsEnterKey = false;
NotificationService notificationService = NotificationService();


Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  sharedPreferences = await SharedPreferences.getInstance();

  appStore.setLanguage(sharedPreferences.getString(SELECTED_LANGUAGE_CODE)??defaultLanguageCode);

 await Firebase.initializeApp().then((value) {
    FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterError;
  });
  await initJsonFile();


  setLogInValue();
  defaultAppButtonShapeBorder = RoundedRectangleBorder(borderRadius: radius(defaultAppButtonRadius));
  oneSignalData();
  await AwesomeNotifications().initialize(
    null,
    [
      NotificationChannel(
        channelKey: 'basic_channel',
        channelName: 'Basic Notifications',
        channelDescription: 'Basic Notification Channel',
        defaultColor: primaryColor,
        playSound: true,
        importance: NotificationImportance.High,
        locked: true,
        enableVibration: true,
      ),
      NotificationChannel(
        channelKey: 'scheduled_channel',
        channelName: 'Scheduled Notifications',
        channelDescription: 'Scheduled Notification Channel',
        defaultColor: primaryColor,
        locked: true,
        importance: NotificationImportance.High,
        playSound: true,
        enableVibration: true,
      ),
    ],
  );
  setTheme();
  if (!getStringAsync(PROGRESS_SETTINGS_DETAIL).isEmptyOrNull) {
    userStore.addAllProgressSettingsListItem(jsonDecode(getStringAsync(PROGRESS_SETTINGS_DETAIL)).map<ProgressSettingModel>((e) => ProgressSettingModel.fromJson(e)).toList());
  } else {
    userStore.addAllProgressSettingsListItem(progressSettingList());
  }

    runApp(MyApp());
}

Future<void> updatePlayerId() async {
  Map req = {
    "player_id": getStringAsync(PLAYER_ID),
    "username": getStringAsync(USERNAME),
    "email": getStringAsync(EMAIL),
  };
  await updateProfileApi(req).then((value) {
    //
  }).catchError((error) {
    //
  });
}

class MyApp extends StatefulWidget {
  static String tag = '/MyApp';

  @override
  MyAppState createState() => MyAppState();
}

class MyAppState extends State<MyApp> {
  late StreamSubscription<List<ConnectivityResult>> _connectivitySubscription;
  bool isCurrentlyOnNoInternet = false;

  @override
  void initState() {
    super.initState();
    init();
  }

  void init() async {
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen((e) {
      if (e == ConnectivityResult.none) {
        log('not connected');
        isCurrentlyOnNoInternet = true;
        push(NoInternetScreen());
      } else {
        if (isCurrentlyOnNoInternet) {
          pop();
          isCurrentlyOnNoInternet = false;
          toast(languages.lblInternetIsConnected);
        }
        log('connected');
      }
    });
  }

  @override
  void didChangeDependencies() {
    if (getIntAsync(THEME_MODE_INDEX) == ThemeModeSystem) appStore.setDarkMode(MediaQuery.of(context).platformBrightness == Brightness.dark);
    super.didChangeDependencies();
  }

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
    _connectivitySubscription.cancel();
  }

  @override
  Widget build(BuildContext context) {
    return Observer(builder: (context) {
      return MaterialApp(
        title: APP_NAME,
        navigatorKey: navigatorKey,
        debugShowCheckedModeBanner: false,
        scrollBehavior: SBehavior(),
        theme: AppTheme.lightTheme,
        darkTheme: AppTheme.darkTheme,
        themeMode: appStore.isDarkMode ? ThemeMode.dark : ThemeMode.light,
        localizationsDelegates: [
          AppLocalizations(),
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        localeResolutionCallback: (locale, supportedLocales) => locale,
        supportedLocales: getSupportedLocales(),
        locale: Locale(appStore.selectedLanguageCode.validate(value: DEFAULT_LANGUAGE)),
        home: SplashScreen(),
      );
    });
  }
}
