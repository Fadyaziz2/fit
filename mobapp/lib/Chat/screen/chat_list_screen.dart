import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/int_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/string_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/extensions/shared_pref.dart';
import 'package:mighty_fitness/models/login_response.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_constants.dart';
import 'package:mighty_fitness/utils/app_images.dart';
import '../components/chat_option_dialog.dart';
import '../components/last_message_container.dart';
import '../../extensions/colors.dart';
import '../../extensions/common.dart';
import '../../extensions/confirmation_dialog.dart';
import '../../extensions/loader_widget.dart';
import '../../extensions/text_styles.dart';
import '../../extensions/widgets.dart';
import '../../main.dart';
import '../../utils/app_common.dart';
import '../model/contact_model.dart';
import 'ChatScreen.dart';
import 'new_chat_screen.dart';

class ChatListScreen extends StatefulWidget {
  ChatListScreen();

  @override
  State<ChatListScreen> createState() => _ChatListScreenState();
}

class _ChatListScreenState extends State<ChatListScreen> with WidgetsBindingObserver {
  String id = '';
  String searchCont = "";

  @override
  void initState() {
    super.initState();
    init();
  }

  UserModel sender = UserModel(
    firstName: getStringAsync(FIRSTNAME) ?? 'Unknown',
    profileImage: getStringAsync(USER_PROFILE_IMG) ?? '',
    uid: getStringAsync(UID) ?? '',
    playerId: getStringAsync(PLAYER_ID) ?? '',
  );

  init() async {
    WidgetsBinding.instance.addObserver(this);
    Map<String, dynamic> presenceStatusTrue = {
      KEY_IS_PRESENT: true,
      KEY_LAST_SEEN: DateTime.now().millisecondsSinceEpoch,
    };
    String? userId = getStringAsync(UID);
    await userService.updateUserStatus(presenceStatusTrue, userId);
    id = userId;
    setState(() {});
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    Map<String, dynamic> presenceStatusFalse = {
      KEY_IS_PRESENT: false,
      KEY_LAST_SEEN: DateTime.now().millisecondsSinceEpoch,
    };

    String? userId = getStringAsync(UID);
    if (state == AppLifecycleState.detached || state == AppLifecycleState.paused) {
      userService.updateUserStatus(presenceStatusFalse, userId);
    }

    if (state == AppLifecycleState.resumed) {
      Map<String, dynamic> presenceStatusTrue = {
        KEY_IS_PRESENT: true,
        KEY_LAST_SEEN: DateTime.now().millisecondsSinceEpoch,
      };
      userService.updateUserStatus(presenceStatusTrue, userId);
    }
  }

  @override
  void dispose() {
    super.dispose();
    WidgetsBinding.instance.removeObserver(this);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: appBarWidget(
        "Chat",
        context: context,
        color: primaryColor,
        textColor: Colors.white,
        showBack: false,
        titleTextStyle: boldTextStyle(size: 18, isHeader: true, color: Colors.white),
        elevation: 1,
        actions: [],
      ),
      body: Stack(
        children: [
          Column(
            children: [
              StreamBuilder<QuerySnapshot>(
                stream: chatMessageService.fetchContacts(userId: getStringAsync(UID) ?? ''),
                builder: (context, snapshot) {
                  if (snapshot.hasError) return Text(snapshot.error.toString(), style: boldTextStyle()).center();
                  if (snapshot.hasData) {
                    if (snapshot.data!.docs.isNotEmpty) {
                      return ListView.builder(
                        itemCount: snapshot.data!.docs.length,
                        shrinkWrap: true,
                        padding: EdgeInsets.only(bottom: 65, top: 8),
                        itemBuilder: (context, index) {
                          ContactModel contact = ContactModel.fromJson(snapshot.data!.docs[index].data() as Map<String, dynamic>);
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              buildChatItemWidget(contact: contact).visible(contact.uid != getStringAsync(UID)),
                            ],
                          );
                        },
                      );
                    } else {
                      return Text("No Conversation Found").center();
                    }
                  }
                  return snapWidgetHelper(snapshot, loadingWidget: Loader().center());
                },
              ).expand(),
            ],
          )
        ],
      ),
      floatingActionButton: FloatingActionButton(
        child: IconButton(
            onPressed: () {
              NewChatScreen().launch(context, pageRouteAnimation: PageRouteAnimation.SlideBottomTop, duration: 300.milliseconds);
            },
            icon: Icon(Icons.chat, color: white)),
        backgroundColor: primaryColor,
        onPressed: () {
          hideKeyboard(context);
          NewChatScreen().launch(context, pageRouteAnimation: PageRouteAnimation.SlideBottomTop, duration: 300.milliseconds);
        },
      ),
    );
  }

  StreamBuilder<List<UserModel>> buildChatItemWidget({required ContactModel contact}) {
    return StreamBuilder(
      stream: chatMessageService.getUserDetailsById(id: contact.uid),
      builder: (context, snap) {
        if (snap.hasData && snap.data!.isNotEmpty) {
          return ListView.builder(
            physics: NeverScrollableScrollPhysics(),
            padding: EdgeInsets.all(0),
            itemCount: snap.data!.length,
            shrinkWrap: true,
            itemBuilder: (context, index) {
              UserModel data = snap.data![index];

              if (snap.data!.length == 0) {
                return Text("No Conversation Found").center();
              }
              return InkWell(
                onTap: () async {
                  if (id != data.uid) {
                    hideKeyboard(context);
                    bool? res = await ChatScreen(userData: data).launch(context);
                  }
                },
                onLongPress: () async {
                  await showInDialog(context, builder: (p0) {
                    return ChatOptionDialog(receiverUser: data);
                  }, contentPadding: EdgeInsets.zero, dialogAnimation: DialogAnimation.SLIDE_TOP_BOTTOM);
                  setState(() {});
                },
                child: Container(
                  width: context.width(),
                  child: Row(
                    children: [
                      (data.profileImage == null || data.profileImage!.isEmpty)
                          ? CircleAvatar(
                              radius: 22,
                              backgroundImage: AssetImage(ic_profile),
                            )
                          : Hero(
                              tag: data.uid ?? 0,
                              child: cachedImage(data.profileImage ?? '', height: 40, width: 40, fit: BoxFit.cover).cornerRadiusWithClipRRect(50),
                            ),
                      10.width,
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text(data.firstName?.capitalizeFirstLetter() ?? '', style: primaryTextStyle(), maxLines: 1, textAlign: TextAlign.start, overflow: TextOverflow.ellipsis).expand(),
                              StreamBuilder<int>(
                                stream: chatMessageService.getUnReadCount(senderId: getStringAsync(UID) ?? '', receiverId: contact.uid.validate()),
                                builder: (context, snap) {
                                  if (snap.hasData && snap.data != 0) {
                                    chatMessageService.fetchForMessageCount(getStringAsync(UID) ?? '');
                                    return Container(
                                      height: 18,
                                      width: 18,
                                      decoration: BoxDecoration(borderRadius: BorderRadius.circular(20), color: primaryColor),
                                      child: Text(snap.data.validate().toString(), style: secondaryTextStyle(size: 12, color: Colors.white)).center(),
                                    );
                                  }
                                  return Offstage();
                                },
                              ),
                            ],
                          ),
                          2.height,
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              LastMessageContainer(stream: chatMessageService.fetchLastMessageBetween(senderId: getStringAsync(UID) ?? '', receiverId: contact.uid!)),
                            ],
                          ),
                        ],
                      ).expand(),
                    ],
                  ).paddingSymmetric(horizontal: 16, vertical: 8),
                ),
              );
            },
          );
        }
        return snapWidgetHelper(snap, loadingWidget: Offstage()).center();
      },
    );
  }
}
