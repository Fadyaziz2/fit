import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/int_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/string_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/models/login_response.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_constants.dart';

import '../../../main.dart';
import '../../extensions/confirmation_dialog.dart';
import '../../extensions/decorations.dart';
import '../../extensions/shared_pref.dart';
import '../../extensions/system_utils.dart';
import '../../extensions/text_styles.dart';
import '../../extensions/widgets.dart';
import '../../utils/app_common.dart';
import '../../utils/app_images.dart';

class ChatUserProfileScreen extends StatefulWidget {
  final String uid;
  final String? heroId;

  ChatUserProfileScreen({required this.uid, this.heroId = ''});

  @override
  _ChatUserProfileScreenState createState() => _ChatUserProfileScreenState();
}

class _ChatUserProfileScreenState extends State<ChatUserProfileScreen> {
  late UserModel currentUser;
  bool isBlocked = false;

  @override
  void initState() {
    super.initState();
    init();
  }

  void init() async {
    isBlocked = await userService.userByEmail(getStringAsync(EMAIL)).then(
        (value) => value.blockedTo!.contains(userService.ref!.doc(widget.uid)));
    setState(() {});
  }

  @override
  void dispose() {
    super.dispose();
  }

  Widget buildImageIconWidget(
      {double? height, double? width, double? roundRadius}) {
    if (currentUser.profileImage?.isNotEmpty??false) {
      return Hero(
        tag: widget.uid,
        child: cachedImage(currentUser.profileImage??'',
                radius: 50,
                height: 100,
                width: 100,
                fit: BoxFit.cover,
                alignment: Alignment.center)
            .cornerRadiusWithClipRRect(50)
            .onTap(() {
          //   FullScreenImageWidget(photoUrl: currentUser.photoUrl.validate(), isFromChat: true, name: currentUser.name.validate()).launch(context);
        }),
      );
    }
    return noProfileImageFound(height: 100, width: 100, isNoRadius: false)
        .onTap(() {
      //
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade100,
      appBar: appBarWidget('', context: context, showBack: true),
      body: StreamBuilder<UserModel>(
        stream: userService.singleUser(widget.uid),
        builder: (context, snap) {
          if (snap.hasData) {
            currentUser = snap.data!;
            return Container(
              height: context.height(),
              color: context.scaffoldBackgroundColor,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  buildImageIconWidget().center(),
                  aboutDetail(),
                  16.height,
                  dividerCommon(context),
                  16.height,
                  statusWidget(),
                  16.height,
                  buildBlockMSG(),
                  //buildReport(),
                ],
              ),
            );
          }
          return snapWidgetHelper(snap);
        },
      ),
    );
  }

  Widget aboutDetail() {
    return Container(
      color: context.cardColor,
      padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      width: context.width(),
      child: Column(
        children: [
          16.height,
          Text("${currentUser.firstName}",
              style: boldTextStyle(letterSpacing: 0.5)),
          8.height,
          if (currentUser.phoneNumber != null)
            Text('+91' + '*' * (currentUser.phoneNumber!.length - 3),
                    style: secondaryTextStyle())
                .visible(!currentUser.phoneNumber.isEmptyOrNull),
          8.height,
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Column(
                children: [
                  IconButton(
                    icon: Image.asset(ic_messages,
                        height: 25, width: 25, color: primaryColor),
                    onPressed: () {
                      finish(context);
                    },
                  ),
                  Text('Message',
                      style: boldTextStyle(
                          size: 12, letterSpacing: 0.5, color: primaryColor)),
                ],
              ),

            ],
          ),
          //     16.height,
        ],
      ),
    );
  }

  Widget statusWidget() {
    return Container(
        //  color: Colors.red,
        //    margin: EdgeInsets.symmetric(vertical: 16, horizontal: 8),
        padding: EdgeInsets.only(left: 8),
        width: context.width(),
        child: Row(
          children: [
            8.width,
            Text(timeAgoSinceDate(currentUser.firebaseUpdatedAt!))
          ],
        ));
  }

  void blockMessage() async {
    List<DocumentReference> temp = [];
    await userService.userByEmail(getStringAsync(EMAIL)).then((value) {
      temp = value.blockedTo!;
    });
    if (!temp.contains(userService.ref!.doc(widget.uid))) {
      temp.add(userService.getUserReference(uid: currentUser.uid.validate()));
    }

    userService.blockUser({KEY_BLOCKED_TO: temp}).then((value) {
      finish(context);
      finish(context);
      finish(context);
    }).catchError((e) {
      //
    });
  }

  Widget buildBlockMSG() {
    return Container(
        //  color: Colors.red,
        //   margin: EdgeInsets.symmetric(vertical: 16, horizontal: 8),
        padding: EdgeInsets.only(left: 10),
        width: context.width(),
        child: Row(
          children: [
            Icon(Icons.block, color: Colors.red[800]),
            8.width,
            Text(isBlocked
                ? "${"Unblock"}${' ' + currentUser.firstName.validate()}"
                : "${"Block"}${' ' + currentUser.firstName.validate()}")
          ],
        )).onTap(() {
      if (isBlocked) {
        unblockDialog(context, receiver: currentUser);
      } else {
        showConfirmDialogCustom(
          context,
          dialogAnimation: DialogAnimation.SCALE,
          title: "Block" + " ${currentUser.firstName.validate()}? ",
          subTitle:
              "Blocked contact will no longer be able to call you or send you message",
          imageShow: Container(
            width: 50,
            height: 50,
            decoration: boxDecorationDefault(
                color: primaryLightColor,
                borderRadius: BorderRadius.circular(40)),
            child: Icon(Icons.block, size: 28, color: primaryColor),
          ),
          onAccept: (v) {
            blockMessage();
          },
          iconColor: primaryColor,
          //  primaryColor: primaryColor,
          positiveText: "Block",
          negativeText: "Cancel",
        );
      }
    });
  }
}
