import 'package:flutter/material.dart';
import '../extensions/colors.dart';
import '../extensions/decorations.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../extensions/loader_widget.dart';
import '../extensions/no_data_widget.dart';
import '../extensions/text_styles.dart';
import '../extensions/widgets.dart';
import '../main.dart';
import '../models/order_response.dart';
import '../network/rest_api.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';

class OrderHistoryScreen extends StatefulWidget {
  const OrderHistoryScreen({super.key});

  @override
  State<OrderHistoryScreen> createState() => _OrderHistoryScreenState();
}

class _OrderHistoryScreenState extends State<OrderHistoryScreen> {
  bool _isLoading = false;
  List<OrderModel> _orders = [];

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders() async {
    setState(() {
      _isLoading = true;
    });

    await orderHistoryApi().then((value) {
      _orders = value.data ?? [];
      setState(() {});
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: appBarWidget(languages.lblOrderHistory,
          context: context, titleSpacing: 0, showBack: true),
      body: RefreshIndicator(
        onRefresh: _loadOrders,
        color: primaryColor,
        child: _isLoading
            ? Loader().center()
            : _orders.isEmpty
                ? SingleChildScrollView(
                    physics: AlwaysScrollableScrollPhysics(),
                    child: SizedBox(
                      height: context.height() * 0.6,
                      child: NoDataWidget(title: languages.lblNoOrdersFound)
                          .center(),
                    ),
                  )
                : ListView.separated(
                    padding: EdgeInsets.all(16),
                    itemCount: _orders.length,
                    separatorBuilder: (_, __) => 12.height,
                    itemBuilder: (context, index) {
                      return _OrderCard(order: _orders[index]);
                    },
                  ),
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  final OrderModel order;

  const _OrderCard({required this.order});

  @override
  Widget build(BuildContext context) {
    final product = order.product;
    final statusColor = _statusColor(order.status);

    return Container(
      decoration: boxDecorationWithRoundedCorners(
        borderRadius: radius(14),
        backgroundColor: appStore.isDarkMode ? cardDarkColor : context.cardColor,
        border: Border.all(color: context.dividerColor),
      ),
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              cachedImage(product?.productImage,
                      height: 70, width: 70, fit: BoxFit.cover)
                  .cornerRadiusWithClipRRect(12),
              12.width,
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(product?.title.validate() ?? '-',
                        style: boldTextStyle(size: 16),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis),
                    6.height,
                    Row(
                      children: [
                        Container(
                          padding:
                              EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: boxDecorationWithRoundedCorners(
                            borderRadius: radius(30),
                            backgroundColor: statusColor.withOpacity(0.12),
                          ),
                          child: Text(order.statusLabel.validate().capitalizeFirstLetter(),
                              style: secondaryTextStyle(
                                  color: statusColor, fontWeight: FontWeight.bold)),
                        ),
                        8.width,
                        Text(order.createdAtFormatted.validate(),
                            style: secondaryTextStyle(size: 12)),
                      ],
                    ),
                  ],
                ),
              )
            ],
          ),
          16.height,
          Row(
            children: [
              Text(languages.lblOrderTotal, style: secondaryTextStyle()),
              8.width,
              Text(
                '${userStore.currencySymbol.validate()}${(order.totalPrice ?? 0).toStringAsFixed(2)}',
                style: boldTextStyle(color: primaryColor),
              ),
            ],
          ),
          8.height,
          Row(
            children: [
              Text(languages.lblPaymentMethod, style: secondaryTextStyle()),
              8.width,
              Text(order.paymentMethodLabel.validate(),
                  style: boldTextStyle()),
            ],
          ),
          8.height,
          Text('${languages.lblQuantity}: ${order.quantity}',
              style: secondaryTextStyle()),
          if (order.statusComment.validate().isNotEmpty) ...[
            12.height,
            Text(languages.lblComment, style: secondaryTextStyle()),
            4.height,
            Text(order.statusComment.validate(), style: primaryTextStyle()),
          ],
          if (order.customerNote.validate().isNotEmpty) ...[
            12.height,
            Text(languages.lblOrderNote, style: secondaryTextStyle()),
            4.height,
            Text(order.customerNote.validate(), style: primaryTextStyle()),
          ],
          12.height,
          Text(languages.lblShippingAddress, style: secondaryTextStyle()),
          4.height,
          Text(order.shippingAddress.validate(value: '-'),
              style: primaryTextStyle()),
        ],
      ),
    );
  }

  Color _statusColor(String? status) {
    switch (status) {
      case 'delivered':
        return Colors.green;
      case 'confirmed':
        return Colors.blueAccent;
      case 'shipped':
        return primaryColor;
      case 'cancelled':
      case 'canceled':
      case 'returned':
        return Colors.redAccent;
      default:
        return Colors.orangeAccent;
    }
  }
}
