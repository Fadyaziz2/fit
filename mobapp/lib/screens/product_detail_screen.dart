import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_vector_icons/flutter_vector_icons.dart';
import 'package:mighty_fitness/extensions/extension_util/num_extensions.dart';

import '../../extensions/extension_util/context_extensions.dart';
import '../../extensions/extension_util/int_extensions.dart';
import '../../extensions/extension_util/string_extensions.dart';
import '../../extensions/extension_util/widget_extensions.dart';
import '../../models/product_response.dart';
import '../components/HtmlWidget.dart';
import '../extensions/app_button.dart';
import '../extensions/decorations.dart';
import '../extensions/system_utils.dart';
import '../extensions/text_styles.dart';
import '../extensions/widgets.dart';
import '../main.dart';
import '../screens/web_view_screen.dart';
import '../utils/app_colors.dart';
import '../utils/app_common.dart';
import '../network/rest_api.dart';

class ProductDetailScreen extends StatefulWidget {
  static String tag = '/productDetailScreen';
  final ProductModel? productModel;

  ProductDetailScreen({this.productModel});

  @override
  ProductDetailScreenState createState() => ProductDetailScreenState();
}

class ProductDetailScreenState extends State<ProductDetailScreen> {
  @override
  void initState() {
    super.initState();
    init();
  }

  init() async {
    if (userStore.adsBannerDetailShowAdsOnProductDetail == 1) loadInterstitialAds();
  }

  @override
  void dispose() {
    if (userStore.adsBannerDetailShowAdsOnProductDetail == 1) showInterstitialAds();
    super.dispose();
  }

  @override
  void setState(fn) {
    if (mounted) super.setState(fn);
  }

  @override
  Widget build(BuildContext context) {
    print("----56--${widget.productModel?.description}");
    final bool hasDiscount = (widget.productModel?.discountActive ?? false) &&
        ((widget.productModel?.finalPrice ?? 0) < (widget.productModel?.price ?? 0));
    final double finalPrice = ((widget.productModel?.finalPrice ?? widget.productModel?.price) ?? 0).toDouble();
    final double originalPrice = (widget.productModel?.price ?? finalPrice).toDouble();
    final double discountPercent = (widget.productModel?.discountPercent ?? 0).toDouble();
    return AnnotatedRegion(
      value: SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: appStore.isDarkMode ? Brightness.dark : Brightness.dark,
        systemNavigationBarIconBrightness: appStore.isDarkMode ? Brightness.dark : Brightness.dark,
      ),
      child: Scaffold(
        bottomNavigationBar: Container(
          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              Expanded(
                child: AppButton(
                  text: languages.lblAddToCart,
                  width: context.width(),
                  color: appStore.isDarkMode ? context.cardColor : Colors.white,
                  textColor: appStore.isDarkMode ? Colors.white : primaryColor,
                  onTap: () {
                    _addProductToCart();
                  },
                ),
              ),
              16.width,
              Expanded(
                child: AppButton(
                  text: languages.lblBuyNow,
                  width: context.width(),
                  color: primaryColor,
                  onTap: () {
                    WebViewScreen(mInitialUrl: widget.productModel!.affiliateLink.toString()).launch(context);
                  },
                ),
              ),
            ],
          ),
        ),
        body: Stack(
          fit: StackFit.expand,
          clipBehavior: Clip.none,
          children: [
            Positioned(
              top: 0,
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  cachedImage(widget.productModel!.productImage.validate(), width: context.width(), height: context.height() * 0.38, fit: BoxFit.fill),
                  Positioned(
                    top: context.statusBarHeight,
                    child: IconButton(
                      onPressed: () {
                        finish(context);
                      },
                      icon: Icon(appStore.selectedLanguageCode == 'ar' ? MaterialIcons.arrow_forward_ios : Octicons.chevron_left, color: primaryColor, size: 28),
                    ),
                  ),
                ],
              ),
            ),
            DraggableScrollableSheet(
              initialChildSize: 0.65,
              minChildSize: 0.65,
              maxChildSize: 0.9,
              builder: (context, controller) => Container(
                width: context.width(),
                decoration: boxDecorationWithRoundedCorners(borderRadius: radiusOnly(topLeft: 20.0, topRight: 20.0), backgroundColor: context.scaffoldBackgroundColor),
                child: SingleChildScrollView(
                  controller: controller,
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    16.height,
                    Text(widget.productModel!.title.validate(), style: boldTextStyle(size: 18)).paddingSymmetric(horizontal: 16),
                    8.height,
                    PriceWidget(
                      price: finalPrice.toStringAsFixed(2),
                      color: primaryColor,
                      textStyle: boldTextStyle(color: primaryColor, size: 20),
                    ).paddingSymmetric(horizontal: 16),
                    if (hasDiscount)
                      Row(
                        children: [
                          Text(originalPrice.toStringAsFixed(2),
                              style: primaryTextStyle(
                                size: 14,
                                decoration: TextDecoration.lineThrough,
                                color: appStore.isDarkMode ? Colors.white70 : Colors.grey,
                              )),
                          8.width,
                          Text('-${discountPercent.toStringAsFixed(0)}%',
                              style: boldTextStyle(color: primaryColor, size: 14)).visible(discountPercent > 0),
                        ],
                      ).paddingSymmetric(horizontal: 16),
                    8.height,
                    Divider(color: appStore.isDarkMode ? Colors.white : context.dividerColor, indent: 16, endIndent: 16),
                    8.height,
                    HtmlWidget(postContent: widget.productModel?.description.toString()??'').paddingSymmetric(horizontal: 8).visible(!(widget.productModel?.description.isEmptyOrNull??false)),
                    80.height,
                  ]),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _addProductToCart() async {
    if (widget.productModel?.id == null) return;
    appStore.setLoading(true);
    Map req = {"product_id": widget.productModel!.id};
    await addToCartApi(req).then((value) {
      toast(value.message);
      widget.productModel!.isInCart = true;
      widget.productModel!.cartQuantity = (widget.productModel!.cartQuantity ?? 0) + 1;
      setState(() {});
    }).catchError((e) {
      print(e);
      appStore.setLoading(false);
    }).whenComplete(() {
      appStore.setLoading(false);
    });
  }
}
