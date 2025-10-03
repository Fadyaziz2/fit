import 'dart:io';


import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/string_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/extensions/html_widget.dart';
import 'package:mighty_fitness/models/login_response.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_constants.dart';
import 'package:paginate_firestore/paginate_firestore.dart';

import '../../../main.dart';
import '../components/ChatitemWidget.dart';
import '../components/chat_top_widget.dart';
import '../../extensions/common.dart';
import '../../extensions/decorations.dart';
import '../../extensions/shared_pref.dart';
import '../../extensions/system_utils.dart';
import '../../extensions/text_styles.dart';
import '../../service/chat_message_service.dart';
import '../../utils/app_images.dart';
import '../model/chat_message_model.dart';
import '../model/file_model.dart';

class ChatScreen extends StatefulWidget {
  final UserModel? userData;

  ChatScreen({this.userData});

  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  String id = '';
  var messageCont = TextEditingController();
  var messageFocus = FocusNode();
  bool isMe = false;
  bool isFirstMsg = false;

  @override
  void initState() {
    super.initState();
    init();
  }

  UserModel sender = UserModel(
    firstName: getStringAsync(FIRSTNAME),
    profileImage: getStringAsync(USER_PROFILE_IMG),
    uid: getStringAsync(UID),
    playerId: getStringAsync(PLAYER_ID),
  );

  init() async {
    log(widget.userData!.toJson().toString());
    id = getStringAsync(UID);
    mIsEnterKey = getBoolAsync(IS_ENTER_KEY, defaultValue: false);
    // mSelectedImage = getStringAsync(SELECTED_WALLPAPER, defaultValue: "assets/default_wallpaper.png");

    chatMessageService = ChatMessageService();
    chatMessageService.setUnReadStatusToTrue(
        senderId: sender.uid!, receiverId: widget.userData?.uid??'');
    setState(() {});
  }

  sendMessage({FilePickerResult? result, File? filepath, String? type}) async {
    ChatMessageModel data = ChatMessageModel();
    data.receiverId = widget.userData!.uid;
    data.senderId = sender.uid;
    data.message = messageCont.text;
    data.isMessageRead = false;
    data.createdAt = DateTime.now().millisecondsSinceEpoch;
    //  data.photoUrl = widget.userData!.profileImage;
    if (result != null) {
      if (type == TYPE_IMAGE) {
        data.messageType = MessageType.IMAGE.name;
      } else {
        data.messageType = MessageType.TEXT.name;
        data.message = messageCont.text.trim();
      }
    } else if (type == TYPE_IMAGE) {
      data.messageType = MessageType.IMAGE.name;
    } else {
      data.messageType = MessageType.TEXT.name;
      data.message = messageCont.text.trim();
    }
    String? message = '';
    if (data.messageType == TYPE_IMAGE) {
      message = " Sent you " + MessageType.IMAGE.name.capitalizeFirstLetter();
    } else {
      message = messageCont.text.trim().validate();
    }
    //   notificationService.sendPushNotifications(getStringAsync(userDisplayName), messageCont.text.trim(), receiverPlayerId: widget.receiverUser!.oneSignalPlayerId).catchError(log);
    notificationService
        .sendPushNotifications(getStringAsync(FIRSTNAME), message,
            recevierUid: widget.userData?.uid.validate(),
            receiverPlayerId: widget.userData?.playerId)
        .catchError((e) {
    });
    messageCont.clear();
    setState(() {});
    return await chatMessageService.addMessage(data).then((value) async {
      String? filePath;
      if (result != null) {
        FileModel fileModel = FileModel();
        fileModel.id = value.id;
        fileModel.file = File(result.files.single.path!);
        fileList.add(fileModel);

        setState(() {});
      } else if (filepath != null && !filepath.path.isEmptyOrNull) {
        FileModel fileModel = FileModel();
        fileModel.id = value.id;
        fileModel.file = File(filepath.path);
        fileList.add(fileModel);
        filePath = filePath;
        setState(() {});
      }

      await chatMessageService
          .addMessageToDb(value, data, sender, widget.userData,
              image: result != null
                  ? File(result.files.single.path!)
                  : (filepath != null && !filepath.path.isEmptyOrNull)
                      ? File(filepath.path)
                      : null)
          .then((value) {
        //
      });

      userService.fireStore
          .collection(USER_COLLECTION)
          .doc(getStringAsync(UID))
          .collection(CONTACT_COLLECTION)
          .doc(widget.userData!.uid)
          .update({
        KEY_LAST_MESSAGE_TIME: DateTime.now().millisecondsSinceEpoch
      }).catchError((e) {
        log("========error while updating data${e.toString()}");
      });
      userService.fireStore
          .collection(USER_COLLECTION)
          .doc(widget.userData!.uid)
          .collection(CONTACT_COLLECTION)
          .doc(getStringAsync(UID))
          .update({
        KEY_LAST_MESSAGE_TIME: DateTime.now().millisecondsSinceEpoch
      }).catchError((e) {
        log("========error while updating data${e.toString()}");
      });
    });
  }

  //region Attchment dialog
  showAttachmentDialog() {
    return showDialog(
      barrierColor: Colors.transparent,
      context: context,
      builder: (context) {
        return Align(
          alignment: Alignment.bottomCenter,
          child: Container(
            padding: EdgeInsets.only(top: 16, bottom: 16, left: 12, right: 12),
            margin: EdgeInsets.only(bottom: 78, left: 12, right: 12),
            decoration: BoxDecoration(
                color: primaryColor, borderRadius: BorderRadius.circular(12)),
            child: Material(
              color: context.primaryColor,
              child: Wrap(
                spacing: 16,
                runSpacing: 16,
                children: [
                  iconsBackgroundWidget(context,
                          name: "camera",
                          image: ic_camera,
                          color: Colors.purple.shade400)
                      .onTap(() async {
                    //

                    var result = await ImagePicker()
                        .pickImage(source: ImageSource.camera);
                    if (result != null) {
                      List<File> image = [];
                      image.add(File(result.path.validate()));
                      // finish(context);
                      sendMessage(
                          result: null,
                          filepath: File(result.path.validate()),
                          type: TYPE_IMAGE);
                      finish(context);
                    } else {
                      // User canceled the picker
                    }
                  }),
                  iconsBackgroundWidget(context,
                          name: "Gallery",
                          image: ic_wallpaper,
                          color: Colors.white)
                      .onTap(() async {
                    //

                    FilePickerResult? result = await FilePicker.platform
                        .pickFiles(
                            type: FileType.image,
                            allowMultiple: true,
                            allowCompression: true);

                    if (result != null) {
                      List<File> image = [];
                      result.files.map((e) {
                        image.add(File(e.path.validate()));
                      }).toList();
                      finish(context);
                      image.forEach((element) {
                        sendMessage(
                            result: result,
                            filepath: File(element.path.validate()),
                            type: TYPE_IMAGE);
                      });
                    } else {
                      // User canceled the picker
                    }
                  }),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    log(widget.userData!.uid.toString());
    return Scaffold(
      appBar: PreferredSize(
        preferredSize: Size(context.width(), kToolbarHeight),
        child: ChatAppBarWidget(
          receiverUser: widget.userData!,
        ),
      ),
      body: Container(
        height: context.height(),
        width: context.width(),
        child: Stack(
          children: [
            Container(
              height: context.height(),
              width: context.width(),
              child: PaginateFirestore(
                reverse: true,
                isLive: true,
                padding: EdgeInsets.only(left: 8, top: 8, right: 8, bottom: 0),
                physics: BouncingScrollPhysics(),
                query: chatMessageService.chatMessagesWithPagination(
                    currentUserId: getStringAsync(UID),
                    receiverUserId: widget.userData!.uid.validate()),
                itemsPerPage: PER_PAGE_CHAT_COUNT,
                shrinkWrap: true,
                onEmpty: Offstage(),
                onLoaded: (page) {
                  isFirstMsg = page.documentSnapshots.isEmpty;
                },
                itemBuilderType: PaginateBuilderType.listView,
                itemBuilder: (context, snap, index) {
                  ChatMessageModel data = ChatMessageModel.fromJson(
                      snap[index].data() as Map<String, dynamic>);
                  data.isMe = data.senderId == sender.uid;

                  return ChatItemWidget(data: data);
                },
              ).paddingBottom(76),
            ),
            Positioned(
              bottom: 16,
              left: 16,
              right: 16,
              child: Container(
                decoration: boxDecorationWithShadow(
                  borderRadius: BorderRadius.circular(30),
                  spreadRadius: 1,
                  blurRadius: 1,
                  backgroundColor: context.cardColor,
                ),
                padding: EdgeInsets.only(left: 8, right: 8),
                child: Row(
                  children: [
                    TextField(
                      controller: messageCont,
                      decoration: InputDecoration(
                        border: InputBorder.none,
                        hintText: "Write a Message",
                        hintStyle: secondaryTextStyle(),
                        contentPadding:
                            EdgeInsets.symmetric(vertical: 18, horizontal: 4),
                      ),
                      cursorColor: Colors.black,
                      focusNode: messageFocus,
                      textCapitalization: TextCapitalization.sentences,
                      keyboardType: TextInputType.multiline,
                      minLines: 1,
                      style: primaryTextStyle(),
                      textInputAction: mIsEnterKey
                          ? TextInputAction.send
                          : TextInputAction.newline,
                      onSubmitted: (s) {
                        sendMessage();
                      },
                      cursorHeight: 20,
                      maxLines: 5,
                    ).expand(),
                    IconButton(
                      visualDensity: VisualDensity(horizontal: 0, vertical: 1),
                      icon: Icon(Icons.attach_file),
                      iconSize: 25.0,
                      padding: EdgeInsets.all(2),
                      color: Colors.grey,
                      onPressed: () {
                        showAttachmentDialog();
                        hideKeyboard(context);
                      },
                    ),
                    IconButton(
                      icon: Icon(Icons.send, color: primaryColor),
                      onPressed: () {
                        if (!messageCont.text.isEmptyOrNull) {
                          sendMessage();
                        }
                      },
                    )
                  ],
                ),
                width: context.width(),
              ),
            )
          ],
        ),
      ),
    );
  }
}
