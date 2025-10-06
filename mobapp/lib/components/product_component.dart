import 'package:flutter/material.dart';
import '../extensions/constants.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../extensions/widgets.dart';
import '../main.dart';
import '../screens/product_detail_screen.dart';
import '../extensions/decorations.dart';
import '../extensions/text_styles.dart';
import '../models/product_response.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';

class ProductComponent extends StatefulWidget {
  static String tag = '/productComponent';

  final ProductModel? mProductModel;
  final Function? onCall;
  final bool showActions;
  final Future<void> Function(ProductModel product)? onToggleFavourite;
  final Future<void> Function(ProductModel product)? onAddToCart;

  ProductComponent({this.mProductModel, this.onCall, this.showActions = false, this.onToggleFavourite, this.onAddToCart});

  @override
  ProductComponentState createState() => ProductComponentState();
}

class ProductComponentState extends State<ProductComponent> {
  @override
  Widget build(BuildContext context) {
    final bool hasDiscount = (widget.mProductModel?.discountActive ?? false) &&
        ((widget.mProductModel?.finalPrice ?? 0) < (widget.mProductModel?.price ?? 0));
    final double finalPrice = ((widget.mProductModel?.finalPrice ?? widget.mProductModel?.price) ?? 0).toDouble();
    final double originalPrice = (widget.mProductModel?.price ?? finalPrice).toDouble();
    final double discountPercent = (widget.mProductModel?.discountPercent ?? 0).toDouble();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Stack(
          children: [
            Container(
              decoration: boxDecorationWithRoundedCorners(borderRadius: radius(12), backgroundColor: GreyLightColor),
              child: cachedImage(widget.mProductModel!.productImage.validate(), height: 155, fit: BoxFit.contain, width: (context.width() - 50) / 2)
                  .cornerRadiusWithClipRRect(defaultRadius),
            ),
            if (widget.showActions)
              Positioned(
                top: 12,
                left: 12,
                child: _actionIcon(
                  context,
                  icon: Icons.add_shopping_cart,
                  isActive: widget.mProductModel?.isInCart ?? false,
                  onTap: () async {
                    if (widget.onAddToCart != null) {
                      await widget.onAddToCart!(widget.mProductModel!);
                      setState(() {});
                    }
                  },
                ),
              ),
            if (widget.showActions)
              Positioned(
                top: 12,
                right: 12,
                child: _actionIcon(
                  context,
                  icon: widget.mProductModel?.isFavourite ?? false ? Icons.favorite : Icons.favorite_border,
                  isActive: widget.mProductModel?.isFavourite ?? false,
                  onTap: () async {
                    if (widget.onToggleFavourite != null) {
                      await widget.onToggleFavourite!(widget.mProductModel!);
                      setState(() {});
                    }
                  },
                ),
              ),
            if (widget.showActions && hasDiscount)
              Positioned(
                bottom: 12,
                left: 12,
                child: Container(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: boxDecorationWithRoundedCorners(
                    borderRadius: radius(20),
                    backgroundColor: primaryColor.withOpacity(0.9),
                  ),
                  child: Text('-${discountPercent.toStringAsFixed(0)}%', style: boldTextStyle(color: Colors.white, size: 12)),
                ),
              ),
          ],
        ),
        8.height,
        PriceWidget(
          price: finalPrice.toStringAsFixed(2),
          textStyle: boldTextStyle(color: primaryColor),
        ),
        if (hasDiscount)
          Row(
            children: [
              Text(originalPrice.toStringAsFixed(2),
                  style: primaryTextStyle(
                    size: 12,
                    decoration: TextDecoration.lineThrough,
                    color: appStore.isDarkMode ? Colors.white70 : Colors.grey,
                  )),
              6.width,
              Text('-${discountPercent.toStringAsFixed(0)}%', style: boldTextStyle(size: 12, color: primaryColor)).visible(discountPercent > 0),
            ],
          ),
        4.height,
        Text(widget.mProductModel!.title.validate(),
                style: primaryTextStyle(color: appStore.isDarkMode ? Colors.white : Colors.black),
                maxLines: 2,
                overflow: TextOverflow.ellipsis)
            .paddingSymmetric(horizontal: 4, vertical: 4),
      ],
    ).onTap(() {
      ProductDetailScreen(productModel: widget.mProductModel!).launch(context).then((value) {
        widget.onCall?.call();
      });
    });
  }

  Widget _actionIcon(BuildContext context,
      {required IconData icon, required bool isActive, required Future<void> Function()? onTap}) {
    return GestureDetector(
      onTap: () async {
        if (onTap != null) {
          await onTap();
        }
      },
      child: Container(
        padding: EdgeInsets.all(6),
        decoration: boxDecorationWithRoundedCorners(
          boxShape: BoxShape.circle,
          backgroundColor: Colors.black.withOpacity(0.35),
          border: Border.all(color: isActive ? primaryColor : Colors.white70, width: 1),
        ),
        child: Icon(icon, size: 18, color: isActive ? primaryColor : Colors.white),
      ),
    );
  }
}
