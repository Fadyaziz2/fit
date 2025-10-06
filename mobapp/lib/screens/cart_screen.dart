import 'package:flutter/material.dart';
import '../extensions/common.dart';
import '../extensions/decorations.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/loader_widget.dart';
import '../extensions/no_data_widget.dart';
import '../extensions/app_button.dart';
import '../extensions/text_styles.dart';
import '../extensions/widgets.dart';
import '../main.dart';
import '../models/cart_response.dart';
import '../network/rest_api.dart';
import '../screens/checkout_screen.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';
import '../extensions/colors.dart';

class CartScreen extends StatefulWidget {
  static String tag = '/CartScreen';

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  bool _isLoading = false;
  CartResponse? _cartResponse;

  @override
  void initState() {
    super.initState();
    _loadCart();
  }

  Future<void> _loadCart() async {
    setState(() {
      _isLoading = true;
    });

    await getCartListApi().then((value) {
      _cartResponse = value;
      cartCountNotifier.value = value.summary?.totalItems ?? 0;
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

  Future<void> _goToCheckout() async {
    if (_cartResponse == null) return;

    bool? orderPlaced = await CheckoutScreen(cartResponse: _cartResponse!)
        .launch(context, pageRouteAnimation: PageRouteAnimation.Slide);

    if (orderPlaced == true) {
      await _loadCart();
    }
  }

  Future<void> _removeItem(int? productId) async {
    if (productId == null) return;

    setState(() {
      _isLoading = true;
    });

    await removeFromCartApi({'product_id': productId}).then((value) {
      toast(value.message);
    }).catchError((e) {
      toast(e.toString());
    }).whenComplete(() async {
      await _loadCart();
    });
  }

  @override
  Widget build(BuildContext context) {
    final cartItems = _cartResponse?.data ?? [];
    final summary = _cartResponse?.summary;

    return Scaffold(
      appBar: appBarWidget(languages.lblAddToCart,
          context: context, titleSpacing: 0, showBack: true),
      body: RefreshIndicator(
        onRefresh: _loadCart,
        color: primaryColor,
        child: _isLoading
            ? Loader()
            : cartItems.isEmpty
                ? NoDataWidget(title: languages.lblNoFoundData)
                    .center()
                : SingleChildScrollView(
                    physics: AlwaysScrollableScrollPhysics(),
                    child: Column(
                      children: [
                        12.height,
                        ...cartItems.map((item) => _CartItemTile(
                              item: item,
                              onRemove: () => _removeItem(item.product?.id),
                            ).paddingSymmetric(horizontal: 16, vertical: 8)),
                        16.height,
                        Container(
                          margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          padding: EdgeInsets.all(16),
                          decoration: boxDecorationWithRoundedCorners(
                            borderRadius: radius(12),
                            backgroundColor: appStore.isDarkMode
                                ? cardDarkColor
                                : cardLightColor,
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('Total', style: boldTextStyle()),
                                  4.height,
                                  Text('Items: ${summary?.totalItems ?? 0}',
                                      style: secondaryTextStyle()),
                                ],
                              ),
                              Text(
                                '${userStore.currencySymbol.validate()}${(summary?.totalAmount ?? 0).toStringAsFixed(2)}',
                                style: boldTextStyle(size: 18, color: primaryColor),
                              )
                            ],
                          ),
                        ),
                        20.height,
                        SizedBox(height: 80),
                      ],
                    ),
                  ),
      ),
      bottomNavigationBar: !_isLoading && cartItems.isNotEmpty
          ? SafeArea(
              top: false,
              child: Container(
                padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: appStore.isDarkMode ? cardDarkColor : context.cardColor,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black12,
                      blurRadius: 8,
                      offset: Offset(0, -2),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(languages.lblOrderTotal,
                            style: secondaryTextStyle()),
                        4.height,
                        Text(
                          '${userStore.currencySymbol.validate()}${(summary?.totalAmount ?? 0).toStringAsFixed(2)}',
                          style: boldTextStyle(size: 18, color: primaryColor),
                        ),
                      ],
                    ).expand(),
                    16.width,
                    AppButton(
                      elevation: 0,
                      color: primaryColor,
                      text: languages.lblCheckout,
                      textStyle: boldTextStyle(color: white),
                      width: context.width() * 0.4,
                      onTap: _goToCheckout,
                    ),
                  ],
                ),
              ),
            )
          : null,
    );
  }
}

class _CartItemTile extends StatelessWidget {
  final CartItemModel item;
  final VoidCallback onRemove;

  const _CartItemTile({required this.item, required this.onRemove});

  @override
  Widget build(BuildContext context) {
    final product = item.product;
    final image = product?.productImage;
    final title = product?.title ?? '-';
    final category = product?.productcategoryTitle ?? '';

    return Container(
      decoration: boxDecorationWithRoundedCorners(
        borderRadius: radius(12),
        backgroundColor:
            appStore.isDarkMode ? cardDarkColor : context.cardColor,
        border: Border.all(color: context.dividerColor),
      ),
      padding: EdgeInsets.all(12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          cachedImage(image, height: 70, width: 70, fit: BoxFit.cover)
              .cornerRadiusWithClipRRect(12),
          12.width,
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: boldTextStyle(), maxLines: 2, overflow: TextOverflow.ellipsis),
                if (category.isNotEmpty)
                  Text(category, style: secondaryTextStyle(size: 12))
                      .paddingTop(4),
                8.height,
                Row(
                  children: [
                    Text('${languages.lblQuantity}: ${item.quantity}',
                        style: secondaryTextStyle()),
                    12.width,
                    Text(
                      '${userStore.currencySymbol.validate()}${(item.unitPrice ?? 0).toStringAsFixed(2)}',
                      style: secondaryTextStyle(),
                    ),
                  ],
                ),
                4.height,
                Text(
                  'Total: ${userStore.currencySymbol.validate()}${(item.totalPrice ?? 0).toStringAsFixed(2)}',
                  style: boldTextStyle(color: primaryColor),
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: onRemove,
            icon: Icon(Icons.delete_outline, color: Colors.redAccent),
            tooltip: 'Remove',
          )
        ],
      ),
    );
  }
}
