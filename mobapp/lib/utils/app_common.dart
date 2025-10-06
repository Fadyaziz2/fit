import 'dart:convert';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:html/parser.dart';
import 'package:intl/intl.dart';
import 'package:mighty_fitness/extensions/colors.dart';
import 'package:mighty_fitness/extensions/confirmation_dialog.dart';
import 'package:mighty_fitness/extensions/system_utils.dart';
import 'package:mighty_fitness/models/login_response.dart';
import 'package:mighty_fitness/screens/dashboard_screen.dart';
import '../extensions/extension_util/int_extensions.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../extensions/extension_util/string_extensions.dart';
import '../../extensions/extension_util/widget_extensions.dart';
import '../../utils/app_colors.dart';
import '../../utils/app_images.dart';
import '../components/adMob_component.dart';
import '../extensions/constants.dart';
import '../extensions/decorations.dart';
import '../extensions/setting_item_widget.dart';
import '../extensions/shared_pref.dart';
import '../extensions/text_styles.dart';
import '../main.dart';
import '../models/progress_setting_model.dart';
import '../models/reminder_model.dart';
import '../models/user_response.dart';
import '../network/rest_api.dart';
import 'app_config.dart';
import 'app_constants.dart';

ValueNotifier<int> cartCountNotifier = ValueNotifier<int>(0);

void setTheme() {
  int themeModeIndex = getIntAsync(THEME_MODE_INDEX, defaultValue: ThemeModeSystem);

  if (themeModeIndex == ThemeModeLight) {
    appStore.setDarkMode(false);
  } else if (themeModeIndex == ThemeModeDark) {
    appStore.setDarkMode(true);
  }
}


class DiagonalPathClipperTwo extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    final path = Path()
      ..lineTo(0.0, size.height)
      ..lineTo(size.width, size.height - 50)
      ..lineTo(size.width, 0.0)
      ..close();
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) {
    return true;
  }
}

Widget outlineIconButton(BuildContext context, {required String text, String? icon, Function()? onTap, Color? textColor}) {
  return OutlinedButton(
    child: Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        if (icon != null) ImageIcon(AssetImage(icon), color: appStore.isDarkMode ? Colors.white : primaryColor, size: 24),
        if (icon != null) SizedBox(width: 8),
        Text(text, style: primaryTextStyle(color: textColor ?? null, size: 14)),
      ],
    ),
    onPressed: onTap ?? () {},
    style: OutlinedButton.styleFrom(
      side: BorderSide(color: textColor ?? (appStore.isDarkMode ? Colors.white38 : primaryColor), style: BorderStyle.solid),
      padding: EdgeInsets.symmetric(horizontal: 10, vertical: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(defaultRadius),
      ),
      backgroundColor: Colors.transparent,
      elevation: 0,
    ),
  );
}

Widget cachedImage(String? url, {double? height, Color? color, double? width, BoxFit? fit, AlignmentGeometry? alignment, bool usePlaceholderIfUrlEmpty = true, double? radius}) {
  if (url.validate().isEmpty) {
    return placeHolderWidget(height: height, width: width, fit: fit, alignment: alignment, radius: radius);
  } else if (url.validate().startsWith('http')) {
    return CachedNetworkImage(
      imageUrl: url!,
      height: height,
      width: width,
      fit: fit,
      color: color,
      alignment: alignment as Alignment? ?? Alignment.center,
      progressIndicatorBuilder: (context, url, progress) {
        return placeHolderWidget(height: height, width: width, fit: fit, alignment: alignment, radius: radius);
      },
      errorWidget: (_, s, d) {
        return placeHolderWidget(height: height, width: width, fit: fit, alignment: alignment, radius: radius);
      },
    );
  } else {
    return Image.asset(ic_placeholder, height: height, width: width, fit: BoxFit.cover, alignment: alignment ?? Alignment.center).cornerRadiusWithClipRRect(radius ?? defaultRadius);
  }
}

Widget placeHolderWidget({double? height, double? width, BoxFit? fit, AlignmentGeometry? alignment, double? radius}) {
  return Image.asset(ic_placeholder, height: height, width: width, fit: BoxFit.cover, alignment: alignment ?? Alignment.center).cornerRadiusWithClipRRect(radius ?? defaultRadius);
}

toast(String? value, {ToastGravity? gravity, length = Toast.LENGTH_SHORT, Color? bgColor, Color? textColor}) {
  Fluttertoast.showToast(
    msg: value.validate(),
    toastLength: length,
    gravity: ToastGravity.BOTTOM,
    timeInSecForIosWeb: 1,
    backgroundColor: bgColor,
    textColor: textColor,
    fontSize: 16.0,
  );
}

setLogInValue() {
  print(getBoolAsync(IS_LOGIN));
  userStore.setLogin(getBoolAsync(IS_LOGIN));
  if (userStore.isLoggedIn) {
    userStore.setToken(getStringAsync(TOKEN));
    userStore.setUserID(getIntAsync(USER_ID));
    userStore.setUserEmail(getStringAsync(EMAIL));
    userStore.setFirstName(getStringAsync(FIRSTNAME));
    userStore.setLastName(getStringAsync(LASTNAME));
    userStore.setUserPassword(getStringAsync(PASSWORD));
    userStore.setUserImage(getStringAsync(USER_PROFILE_IMG));
    userStore.setPhoneNo(getStringAsync(PHONE_NUMBER));
    userStore.setDisplayName(getStringAsync(DISPLAY_NAME));
    userStore.setGender(getStringAsync(GENDER));
    userStore.setAge(getStringAsync(AGE));
    userStore.setHeight(getStringAsync(HEIGHT));
    userStore.setHeightUnit(getStringAsync(HEIGHT_UNIT));
    userStore.setWeight(getStringAsync(WEIGHT));
    userStore.setWeightUnit(getStringAsync(WEIGHT_UNIT));

    if (!getStringAsync(SUBSCRIPTION_DETAIL).isEmptyOrNull) {
      SubscriptionDetail? subscriptionDetail = SubscriptionDetail.fromJson(jsonDecode(getStringAsync(SUBSCRIPTION_DETAIL)));
      userStore.setSubscribe(getIntAsync(IS_SUBSCRIBE));
      userStore.setSubscriptionDetail(subscriptionDetail);
    }
    String notificationData = getStringAsync(NOTIFICATION_DETAIL);
    if (notificationData.isNotEmpty) {
      Iterable mList = jsonDecode(getStringAsync(NOTIFICATION_DETAIL));
      notificationStore.mRemindList = mList.map((model) => ReminderModel.fromJson(model)).toList();
    }
  }
}

String parseHtmlString(String? htmlString) {
  return parse(parse(htmlString).body!.text).documentElement!.text;
}

String parseDocumentDate(DateTime dateTime, [bool includeTime = false]) {
  if (includeTime) {
    return DateFormat('dd MMM, yyyy hh:mm a').format(dateTime);
  } else {
    return DateFormat('dd MMM, yyyy').format(dateTime);
  }
}

Duration parseDuration(String durationString) {
  List<String> components = durationString.split(':');

  int hours = int.parse(components[0]);
  int minutes = int.parse(components[1]);
  int seconds = int.parse(components[2]);

  return Duration(hours: hours, minutes: minutes, seconds: seconds);
}

progressDateStringWidget(String date) {
  DateFormat dateFormat = DateFormat('yyyy-MM-dd');
  DateTime dateTime = DateTime.parse(date);
  var dateValue = dateFormat.format(dateTime);
  return dateValue;
}

Future<void> launchUrls(String url, {bool forceWebView = false}) async {
  await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication).catchError((e) {
    log(e);
    toast('Invalid URL: $url');
  });
}

Widget mBlackEffect(double? width, double? height, {double? radiusValue = 16}) {
  return Container(
    width: width,
    height: height,
    decoration: BoxDecoration(
      borderRadius: radius(radiusValue),
      gradient: LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [
          Colors.black.withOpacity(0.2),
          Colors.black.withOpacity(0.2),
          Colors.black.withOpacity(0.4),
          Colors.black.withOpacity(0.4),
        ],
      ),
    ),
    alignment: Alignment.bottomLeft,
  );
}

Widget mOption(String img, String title, Function? onCall) {
  return SettingItemWidget(
    padding: EdgeInsets.symmetric(vertical: 16, horizontal: 16),
    title: title,
    leading: Image.asset(img, width: 20, height: 20, color: textPrimaryColorGlobal),
    trailing: appStore.selectedLanguageCode == 'ar' ? Icon(Icons.chevron_left, color: grayColor) : Icon(Icons.chevron_right, color: grayColor),
    onTap: () async {
      onCall!.call();
    },
  );
}

Future<void> getSettingData() async {
  await getAppSettingApi().then((value) {
    print('------------------238------------>>-${value.toJson()}');

    app_update_check=value.appVersion;
    print("fkfjkjfjfdfj:${value.appVersion}");
    print("fasdfkjfeueir:$app_update_check");
    setValue(SITE_NAME, value.siteName.validate());
    setValue(SITE_DESCRIPTION, value.siteDescription.validate());
    setValue(SITE_COPYRIGHT, value.siteCopyright.validate());
    setValue(FACEBOOK_URL, value.facebookUrl.validate());
    setValue(INSTAGRAM_URL, value.instagramUrl.validate());
    setValue(TWITTER_URL, value.twitterUrl.validate());
    setValue(LINKED_URL, value.linkedinUrl.validate());
    setValue(CONTACT_EMAIL, value.contactEmail.validate());
    setValue(CONTACT_NUMBER, value.contactNumber.validate());
    setValue(HELP_SUPPORT, value.helpSupportUrl.validate());
    setValue(PRIVACY_POLICY, value.helpSupportUrl.validate());
    setValue(TERMS_SERVICE, value.helpSupportUrl.validate());
    setValue(CRISP_CHAT_ENABLED, value.crisp_chat?.isCrispChatEnabled ?? false);
    setValue(
      CRISP_CHAT_WEB_SITE_ID,
      (value.crisp_chat?.crispChatWebsiteId).validate(),
    );

  });
}

Future<void> getUSerDetail(BuildContext context, int? id) async {
  await getUserDataApi(id: id.validate()).then((value) async {
    userStore.setFirstName(value.data!.firstName.validate());
    userStore.setUserEmail(value.data!.email.validate());
    userStore.setLastName(value.data!.lastName.validate());
    userStore.setGender(value.data!.gender.validate());
    userStore.setUserID(value.data!.id.validate());
    print("------265>>>${value.data!.phoneNumber}");
    userStore.setPhoneNo(value.data!.phoneNumber.validate());
    userStore.setUsername(value.data!.username.validate());
    userStore.setDisplayName(value.data!.displayName.validate());
    userStore.setUserImage(value.data!.profileImage.validate());
    final profile = value.data!.userProfile;
    if (profile != null) {
      userStore.setAge(profile.age.validate());
      userStore.setHeight(profile.height.validate());
      userStore.setWeight(profile.weight.validate());
      userStore.setWeightUnit(profile.weightUnit.validate());
      userStore.setHeightUnit(profile.heightUnit.validate());
      await userStore.setAssignedSpecialistId(profile.specialistId);
      await userStore.setFreeBookingUsedAt(profile.freeBookingUsedAt);
      await userStore.setAssignedSpecialistBranchId(profile.specialist?.branchId);
    } else {
      await userStore.setAssignedSpecialistId(null);
      await userStore.setFreeBookingUsedAt(null);
      await userStore.setAssignedSpecialistBranchId(null);
    }
    userStore.setSubscribe(value.subscriptionDetail!.isSubscribe.validate());
    userStore.setSubscriptionDetail(value.subscriptionDetail!);
    print("user data->" + value.toJson().toString());
    cartCountNotifier.value = value.data?.cartItemCount ?? 0;
    appStore.setLoading(false);
  }).catchError((e) {
    print("error-" + e.toString());
    appStore.setLoading(false);
  });
}

void showInterstitialAds() {
  if (userStore.isSubscribe == 0) {
    adShow();
  }
}

void loadInterstitialAds() {
  if (userStore.isSubscribe == 0) {
    createInterstitialAd();
  }
}

void oneSignalData() {
  OneSignal.Debug.setLogLevel(OSLogLevel.verbose);
  OneSignal.Debug.setAlertLevel(OSLogLevel.none);
  OneSignal.consentRequired(false);

  OneSignal.initialize(mOneSignalID);

  OneSignal.User.pushSubscription.addObserver((state) async {
    print(OneSignal.User.pushSubscription.optedIn);
    print(OneSignal.User.pushSubscription.id);
    print(OneSignal.User.pushSubscription.token);
    await setValue(PLAYER_ID, OneSignal.User.pushSubscription.id);
  });
  if (userStore.isLoggedIn) {
    updatePlayerId();
  }
}

Widget mSuffixTextFieldIconWidget(String? img) {
  return Image.asset(img.validate(), height: 20, width: 20, color: Colors.grey).paddingAll(14);
}

List<ProgressSettingModel> progressSettingList() {
  return [
    ProgressSettingModel(id: 1, name: 'Weight', isEnable: true),
    ProgressSettingModel(id: 2, name: 'Heart Rate', isEnable: true),
    ProgressSettingModel(id: 3, name: 'Push ups in 1 minutes', isEnable: true),
  ];
}

Widget mPro() {
  return Container(
    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 2),
    decoration: boxDecorationWithRoundedCorners(backgroundColor: primaryColor, borderRadius: radius(6)),
    child: Text(languages.lblPro, style: primaryTextStyle(color: Colors.white, size: 12)),
  );
}

Widget noProfileImageFound(
    {double? height, double? width, bool isNoRadius = true}) {
  return Image.asset(
    ic_profile,
    height: height,
    width: width,
    fit: BoxFit.cover,
    // color: iconColor,
  ).cornerRadiusWithClipRRect(isNoRadius ? 0 : height! / 2);
}

UserModel sender = UserModel(
  firstName: getStringAsync(FIRSTNAME),
  profileImage: getStringAsync(USER_PROFILE_IMG),
  uid: getStringAsync(UID),
  playerId: getStringAsync(PLAYER_ID),
);


dividerCommon(context) {
  return Divider(
    color: viewLineColor,
    height: 8,
    thickness: 1,
  );
}

noteCommon() {
  return Container(
    padding: EdgeInsets.symmetric(horizontal: 16, vertical: 16),
    decoration: boxDecorationWithRoundedCorners(
      backgroundColor: Colors.grey.shade200,
      borderRadius: radius(defaultRadius),
    ),
    child: Text(
      "NOTE: we don't collect, process, or store any of the data that you enter while using this tool. All calculation are done exclusively in your locally, and we don't have access to the results. All data will be permanently erased after leaving or close the screen.",
      style: boldTextStyle(size: 8, color: Colors.grey),
    ),
  ).paddingSymmetric(horizontal: 16);
}

List<String> setSearchParam(String caseNumber) {
  List<String> caseSearchList = [];
  String temp = "";
  for (int i = 0; i < caseNumber.length; i++) {
    temp = temp + caseNumber[i];
    caseSearchList.add(temp.toLowerCase());
  }
  return caseSearchList;
}

double poundsToKilograms(double pounds) {
  return pounds * 0.453592;
}



void unblockDialog(BuildContext context, {required UserModel receiver}) async {
  await showConfirmDialogCustom(
    context,
    dialogType: DialogType.CONFIRMATION,
    primaryColor: primaryColor,
    imageShow: Container(
      width: 50,
      height: 50,
      decoration: boxDecorationDefault(
          color: primaryLightColor, borderRadius: BorderRadius.circular(40)),
      child: Icon(Icons.block, size: 28, color: primaryColor),
    ),
    title: 'Unblock ${receiver.firstName} to send a message',
    dialogAnimation: DialogAnimation.SCALE,
    positiveText: "Unblock".capitalizeFirstLetter(),
    negativeText: 'cancel'.capitalizeFirstLetter(),
    onAccept: (v) async {
      List<DocumentReference> temp = [];

      temp = await userService
          .userByEmail(getStringAsync(EMAIL))
          .then((value) => value.blockedTo!);

      if (temp.contains(
          userService.getUserReference(uid: receiver.uid.validate()))) {
        temp.removeWhere((element) =>
        element ==
            userService.getUserReference(uid: receiver.uid.validate()));
      }

      userService.unBlockUser({KEY_BLOCKED_TO: temp}).then((value) {
        finish(context);
      }).catchError((e) {
        //
      });
    },
  );
}



String timeAgoSinceDate(Timestamp dateString) {
  Duration difference = DateTime.now().difference(dateString.toDate());

  if (difference.inSeconds < 60) {
    return '${difference.inSeconds} seconds ago';
  } else if (difference.inMinutes < 60) {
    return '${difference.inMinutes} minutes ago';
  } else if (difference.inHours < 24) {
    return '${difference.inHours} hours ago';
  } else if (difference.inDays < 7) {
    return '${difference.inDays} days ago';
  } else if ((difference.inDays / 7).floor() < 4) {
    return '${(difference.inDays / 7).floor()} weeks ago';
  } else if ((difference.inDays / 30).floor() < 12) {
    return '${(difference.inDays / 30).floor()} months ago';
  } else {
    return '${(difference.inDays / 365).floor()} years ago';
  }
}