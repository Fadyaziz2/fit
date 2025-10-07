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
            ? ListView(
                physics: AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.symmetric(vertical: 32),
                children: [Loader().center()],
              )
            : cartItems.isEmpty
                ? ListView(
                    physics: AlwaysScrollableScrollPhysics(),
                    padding: EdgeInsets.symmetric(vertical: context.height() * 0.15),
                    children: [
                      NoDataWidget(title: languages.lblNoFoundData)
                          .paddingSymmetric(horizontal: 16),
                    ],
                  )
                : ListView.separated(
                    physics: AlwaysScrollableScrollPhysics(),
                    padding: EdgeInsets.fromLTRB(16, 16, 16, 140),
                    itemCount: cartItems.length + 1,
                    separatorBuilder: (_, __) => 16.height,
                    itemBuilder: (_, index) {
                      if (index == cartItems.length) {
                        return _CartSummaryCard(summary: summary);
                      }

                      final item = cartItems[index];

                      return _CartItemTile(
                        item: item,
                        onRemove: () => _removeItem(item.product?.id),
                      );
                    },
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
        borderRadius: radius(16),
        backgroundColor:
            appStore.isDarkMode ? cardDarkColor : context.cardColor,
        border: Border.all(color: context.dividerColor.withOpacity(0.4)),
      ),
      padding: EdgeInsets.all(14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          cachedImage(image, height: 80, width: 80, fit: BoxFit.cover)
              .cornerRadiusWithClipRRect(16),
          14.width,
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: boldTextStyle(size: 16),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (category.isNotEmpty)
                  Text(category, style: secondaryTextStyle(size: 12))
                      .paddingTop(4),
                12.height,
                Wrap(
                  spacing: 12,
                  runSpacing: 8,
                  children: [
                    _InfoChip(
                      icon: Icons.shopping_bag_outlined,
                      label:
                          '${languages.lblQuantity}: ${item.quantity.validate()}',
                    ),
                    _InfoChip(
                      icon: Icons.attach_money,
                      label:
                          '${userStore.currencySymbol.validate()}${(item.unitPrice ?? 0).toStringAsFixed(2)}',
                    ),
                  ],
                ),
                12.height,
                Text(
                  '${languages.lblOrderTotal}: ${userStore.currencySymbol.validate()}${(item.totalPrice ?? 0).toStringAsFixed(2)}',
                  style: boldTextStyle(color: primaryColor),
                ),
              ],
            ),
          ),
          IconButton(
            onPressed: onRemove,
            icon: Icon(Icons.delete_outline, color: Colors.redAccent),
            tooltip: languages.lblDelete,
          )
        ],
      ),
    );
  }
}

class _CartSummaryCard extends StatelessWidget {
  final CartSummary? summary;

  const _CartSummaryCard({this.summary});

  @override
  Widget build(BuildContext context) {
    final totalItems = summary?.totalItems ?? 0;
    final totalAmount = summary?.totalAmount ?? 0;

    return Container(
      decoration: boxDecorationWithRoundedCorners(
        borderRadius: radius(16),
        backgroundColor: appStore.isDarkMode ? cardDarkColor : cardLightColor,
      ),
      padding: EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(languages.lblOrderSummary, style: boldTextStyle(size: 18)),
          12.height,
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(languages.lblQuantity, style: secondaryTextStyle()),
                    4.height,
                    Text('$totalItems', style: boldTextStyle(size: 16)),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(languages.lblOrderTotal, style: secondaryTextStyle()),
                    4.height,
                    Text(
                      '${userStore.currencySymbol.validate()}${totalAmount.toStringAsFixed(2)}',
                      style: boldTextStyle(size: 18, color: primaryColor),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _InfoChip({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: context.cardColor,
        borderRadius: radius(20),
        border: Border.all(color: context.dividerColor.withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: context.iconColor.withOpacity(0.6)),
          6.width,
          Text(label, style: secondaryTextStyle(size: 12)),
        ],
      ),
    );
  }
}
