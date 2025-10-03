import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:mighty_fitness/extensions/extension_util/int_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import '../../../extensions/colors.dart';
import '../../../extensions/shared_pref.dart';
import '../../../extensions/text_styles.dart';
import '../../../utils/app_constants.dart';
import '../model/chat_message_model.dart';

class LastMessageContainer extends StatelessWidget {
  final stream;

  LastMessageContainer({required this.stream});

  Widget typeWidget(ChatMessageModel message) {
    String? type = message.messageType;
    switch (type) {
      case TEXT:
        // return Text("${message.isEncrypt == true ? decryptedData(message.message.validate()) : message.message.validate()}",
        //         maxLines: 1, overflow: TextOverflow.ellipsis, style: secondaryTextStyle(size: 12))
        //     .expand();
        return Text("${message.message}",
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: secondaryTextStyle(size: 12))
            .expand();
      case IMAGE:
        return Row(
          children: [
            Icon(Icons.photo_sharp, size: 16, color: textSecondaryColor),
            4.width,
            Text('image', style: secondaryTextStyle(color: textSecondaryColor)),
          ],
        );
      default:
        return Container();
    }
  }

  @override
  Widget build(BuildContext context) {
    return StreamBuilder(
      stream: stream,
      builder: (context, AsyncSnapshot<QuerySnapshot> snapshot) {
        if (snapshot.hasData) {
          var docList = snapshot.data!.docs;

          if (docList.isNotEmpty) {
            ChatMessageModel message = ChatMessageModel.fromJson(
                docList.last.data() as Map<String, dynamic>);

            message.isMe = message.senderId == getStringAsync(UID);
            String time = '';
            DateTime date =
                DateTime.fromMicrosecondsSinceEpoch(message.createdAt! * 1000);
            if (date.day == DateTime.now().day) {
              time = DateFormat('hh:mm a').format(
                  DateTime.fromMicrosecondsSinceEpoch(
                      message.createdAt! * 1000));
            } else {
              time = DateFormat('dd/MM/yyy').format(
                  DateTime.fromMicrosecondsSinceEpoch(
                      message.createdAt! * 1000));
            }
            return Row(
              children: [
                Row(
                  children: [
                    message.isMe!
                        ? !message.isMessageRead!
                            ? Icon(Icons.done,
                                size: 16, color: textSecondaryColor)
                            : Icon(Icons.done_all,
                                size: 16, color: primaryColor)
                        : SizedBox(),
                    4.width,
                    typeWidget(message),
                  ],
                ).expand(),
                Text(time,
                    style: secondaryTextStyle(
                        size: 12, color: whiteColor.withOpacity(0.9))),
              ],
            ).paddingTop(2).expand();
          }
          return Text("",
              style:
                  TextStyle(color: whiteColor.withOpacity(0.9), fontSize: 14));
        }
        return Text("..",
            style: TextStyle(color: whiteColor.withOpacity(0.9), fontSize: 14));
      },
    );
  }
}
