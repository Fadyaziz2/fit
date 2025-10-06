import 'package:flutter/material.dart';
import '../extensions/colors.dart';
import '../extensions/decorations.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../extensions/widgets.dart';
import '../extensions/animatedList/animated_list_view.dart';
import '../extensions/text_styles.dart';
import '../main.dart';
import '../models/notification_response.dart';
import '../network/rest_api.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';
import '../utils/app_images.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  @override
  void initState() {
    super.initState();
  }

  Future<void> getNotificationStatus(String? id) async {
    appStore.setLoading(true);
    await notificationStatusApi(id.validate()).then((value) {
      notificationCountNotifier.value = value.allUnreadCount ?? 0;
      setState(() {});
    }).catchError((e) {
      appStore.setLoading(false);
      setState(() {});
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: appBarWidget(languages.lblNotifications, context: context),
      body: FutureBuilder<NotificationResponse>(
          future: notificationApi(),
          builder: (context, snapshot) {
            if (snapshot.hasData) {
              final response = snapshot.data!;
              notificationCountNotifier.value = response.allUnreadCount ?? 0;

              return response.notificationData!.isNotEmpty
                  ? AnimatedListView(
                      itemCount: response.notificationData!.length,
                      padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      shrinkWrap: true,
                      physics: BouncingScrollPhysics(),
                      itemBuilder: (context, i) {
                        NotificationData mNotification = response.notificationData![i];
                        return Container(
                          decoration: appStore.isDarkMode ? boxDecorationWithRoundedCorners(backgroundColor: cardDarkColor, borderRadius: radius(16)) : boxDecorationRoundedWithShadow(16),
                          padding: EdgeInsets.all(12),
                          margin: EdgeInsets.only(bottom: 16),
                          child: Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                mNotification.data!.image.isEmptyOrNull
                                    ? Container(
                                        width: 55,
                                        height: 55,
                                        decoration: boxDecorationWithRoundedCorners(borderRadius: radius(10), backgroundColor: primaryOpacity),
                                        child: Text(mNotification.data!.subject!.isNotEmpty ? mNotification.data!.subject![0].toUpperCase() : '', style: boldTextStyle(color: primaryColor, size: 24)).center(),
                                      )
                                    : cachedImage(mNotification.data!.image.toString(), width: 55, height: 55, fit: BoxFit.cover).cornerRadiusWithClipRRect(10),
                                10.width,
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    2.height,
                                    Text(mNotification.data!.subject.validate().capitalizeFirstLetter(), style: boldTextStyle()),
                                    2.height,
                                    Text(mNotification.data!.message.validate(), style: secondaryTextStyle(color: textColor), maxLines: 2, overflow: TextOverflow.ellipsis)
                                  ],
                                ).expand(),
                              ],
                            ),
                            5.height,
                            Align(
                              alignment: Alignment.bottomRight,
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  Text(mNotification.createdAt.validate(), style: secondaryTextStyle(size: 12)),
                                  4.width,
                                  mNotification.readAt.isEmptyOrNull ? Icon(Icons.circle, size: 8, color: GreenColor) : SizedBox(),
                                ],
                              ),
                            ),
                          ]),
                        ).onTap(() {
                          if (mNotification.readAt.isEmptyOrNull) {
                            getNotificationStatus(mNotification.id.toString());
                            setState(() {});
                          }
                        });
                      },
                    )
                  : Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Image.asset(no_data_found, height: context.height() * 0.2, width: context.width() * 0.4),
                        16.height,
                        Text(languages.lblNotificationEmpty, style: boldTextStyle()),
                      ],
                    ).center();
            }
            return snapWidgetHelper(snapshot);
          }),
    );
  }
}
