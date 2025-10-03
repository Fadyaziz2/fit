import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:marquee/marquee.dart';
import 'package:mighty_fitness/extensions/date_time_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/int_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/models/login_response.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_constants.dart';

import '../../../../main.dart';
import '../../../extensions/colors.dart';
import '../../../extensions/common.dart';
import '../../../extensions/confirmation_dialog.dart';
import '../../../extensions/decorations.dart';
import '../../../extensions/shared_pref.dart';
import '../../../extensions/system_utils.dart';
import '../../../extensions/text_styles.dart';
import '../../../extensions/widgets.dart';
import '../../../utils/app_common.dart';
import '../../../utils/app_images.dart';
import '../screen/chat_user_profile_screen.dart';

class ChatAppBarWidget extends StatefulWidget {
  final UserModel? receiverUser;

  ChatAppBarWidget({required this.receiverUser});

  @override
  ChatAppBarWidgetState createState() => ChatAppBarWidgetState();
}

class ChatAppBarWidgetState extends State<ChatAppBarWidget> {
  bool isBlocked = false;

  @override
  void initState() {
    super.initState();
    init();
  }

  Future<void> init() async {
    isBlocked = await userService.userByEmail(getStringAsync(EMAIL)).then(
        (value) => value.blockedTo!
            .contains(userService.ref!.doc(widget.receiverUser!.uid)));

    // await chatRequestService.isRequestsUserExist(widget.receiverUser!.uid!).then((value) {
    //   isRequestAccept = !value;
    //   setState(() {});
    // });
  }

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
  }

  @override
  Widget build(BuildContext context) {
    String getTime(int val) {
      String? time;
      DateTime date = DateTime.fromMicrosecondsSinceEpoch(val * 1000);
      if (date.day == DateTime.now().day) {
        time = "at ${DateFormat('hh:mm a').format(date)}";
      } else {
        time = date.timeAgo;
      }
      return time;
    }

    return AppBar(
      automaticallyImplyLeading: false,
      title: StreamBuilder<UserModel>(
        stream: userService.singleUser(widget.receiverUser?.uid ?? ''),
        builder: (context, snap) {
          if (snap.hasError) {
            return Container();
          }
          if (snap.hasData) {
            UserModel data = snap.data!;

            return Row(
              children: [
                InkWell(
                  splashColor: Colors.transparent,
                  hoverColor: Colors.transparent,
                  highlightColor: Colors.transparent,
                  onTap: () {
                    Navigator.of(context).popUntil((route) => route.isFirst);
                  },
                  child: Icon(Icons.arrow_back, color: whiteColor),
                ),
                8.width,
                InkWell(
                  splashColor: Colors.transparent,
                  hoverColor: Colors.transparent,
                  highlightColor: Colors.transparent,
                  onTap: () {
                    //  FullScreenImageWidget(photoUrl: data.photoUrl, heroId: data.uid, name: data.name).launch(context);
                  },
                  child: Row(
                    children: [
                      (data.profileImage != null &&
                              data.profileImage!.isNotEmpty)
                          ? Hero(
                              tag: data.uid??'',
                              child: CircleAvatar(
                                  radius: 22,
                                  backgroundImage: AssetImage(ic_profile)),
                            )
                          : Hero(
                              tag: data.uid ?? '', // Ensure this is non-null
                              child: cachedImage(data.profileImage,
                                      height: 35, width: 35, fit: BoxFit.cover)
                                  .cornerRadiusWithClipRRect(50),
                            ),
                    ],
                  ).paddingSymmetric(vertical: 16),
                ),
                8.width,
                InkWell(
                  splashColor: Colors.transparent,
                  hoverColor: Colors.transparent,
                  highlightColor: Colors.transparent,
                  onTap: () {
                    ChatUserProfileScreen(uid: data.uid??'').launch(
                        context,
                        pageRouteAnimation: PageRouteAnimation.Scale,
                        duration: 300.milliseconds);
                  },
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(data.firstName ?? '',
                          style: boldTextStyle(
                              color: whiteColor)), // Null-safe check
                      4.height,
                      data.isPresence ==
                              true // Check if isPresence is non-null and true
                          ? Text('Online',
                              style: secondaryTextStyle(color: Colors.white70))
                          : Marquee(
                        text: "Last seen" +
                            " ${getTime(data.lastSeen?.validate() ?? 0)}",
                        style: secondaryTextStyle(size: 12, color:  Colors.white70),
                        scrollAxis: Axis.horizontal,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        pauseAfterRound: const Duration(seconds: 1),
                      ),

                    ],
                  ).paddingSymmetric(vertical: 16),
                ).expand(),
              ],
            );
          }

          return snapWidgetHelper(snap, loadingWidget: Container());
        },
      ),
      actions: [
        PopupMenuButton(
          padding: EdgeInsets.zero,
          offset: Offset(10, -40),
          icon: Icon(Icons.more_vert, color: whiteColor),
          color: white,
          onSelected: (dynamic value) async {
            if (value == 1) {
              ChatUserProfileScreen(
                      uid: widget.receiverUser?.uid ??
                          '') // Null-safe check
                  .launch(context,
                      pageRouteAnimation: PageRouteAnimation.Scale,
                      duration: 300.milliseconds);
            } else if (value == 2) {
              if (isBlocked) {
                unblockDialog(context, receiver: widget.receiverUser!);
              } else {
                showConfirmDialogCustom(
                  context,
                  dialogAnimation: DialogAnimation.SCALE,
                  title: "Block" +
                      " ${widget.receiverUser?.firstName??''}?",
                  subTitle:
                      "Blocked contact will no longer be able to call you or send you messages.",
                  onAccept: (v) {
                    blockMessage();
                  },
                  imageShow: Container(
                    width: 50,
                    height: 50,
                    decoration: boxDecorationDefault(
                        color: primaryLightColor,
                        borderRadius: BorderRadius.circular(40)),
                    child: Icon(Icons.block, size: 28, color: primaryColor),
                  ),
                  positiveText: "Block",
                  negativeText: "Cancel",
                  primaryColor: primaryColor,
                );
              }
            } else if (value == 3) {
              showConfirmDialogCustom(
                context,
                dialogAnimation: DialogAnimation.SCALE,
                title: 'Clear chats?',
                positiveText: 'Yes',
                negativeText: "No",
                primaryColor: primaryColor,
                imageShow: Container(
                  width: 50,
                  height: 50,
                  decoration: boxDecorationDefault(
                      color: primaryLightColor,
                      borderRadius: BorderRadius.circular(40)),
                  child: Icon(Icons.clear, size: 28, color: primaryColor),
                ),
                onAccept: (v) {
                  chatMessageService
                      .clearAllMessages(
                          senderId: sender.uid,
                          receiverId:
                              widget.receiverUser?.uid ?? '')
                      .then((value) {
                    toast("Chat cleared");
                    hideKeyboard(context);
                  }).catchError((e) {
                    toast(e);
                  });
                },
              );
            }
          },
          itemBuilder: (context) {
            List<PopupMenuItem> list = [];
            list.add(PopupMenuItem(
                value: 1,
                child: Text("View Contact", style: primaryTextStyle())));
            list.add(PopupMenuItem(
                value: 2,
                child: Text(isBlocked ? 'Unblock' : 'Block',
                    style: primaryTextStyle())));
            list.add(PopupMenuItem(
                value: 3,
                child: Text('Clear Chat', style: primaryTextStyle())));

            return list;
          },
        ),
      ],
      backgroundColor: context.primaryColor,
    );
  }

  void blockMessage() async {
    List<DocumentReference> temp = [];
    await userService.userByEmail(getStringAsync(EMAIL)).then((value) {
      temp = value.blockedTo!;
    });
    if (!temp.contains(userService.ref!.doc(widget.receiverUser?.uid))) {
      temp.add(userService.getUserReference(
          uid: widget.receiverUser?.uid??""));
    }

    userService.blockUser({KEY_BLOCKED_TO: temp}).then((value) {
      finish(context);
      finish(context);
      finish(context);
    }).catchError((e) {
      //
    });
  }


}
