import 'package:flutter/material.dart';
import '../extensions/decorations.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../main.dart';
import '../models/product_category_response.dart';
import '../utils/app_common.dart';
import '../extensions/text_styles.dart';
import '../screens/view_all_product_screen.dart';
import '../utils/app_colors.dart';

class ProductCategoryComponent extends StatefulWidget {
  final ProductCategoryModel? mProductCategoryModel;
  final isGrid;
  final Function? onCall;

  const ProductCategoryComponent({super.key, this.mProductCategoryModel, this.isGrid = false, this.onCall});

  @override
  State<ProductCategoryComponent> createState() => _ProductCategoryComponentState();
}

class _ProductCategoryComponentState extends State<ProductCategoryComponent> {
  @override
  Widget build(BuildContext context) {
    final double itemSize = widget.isGrid ? (context.width() - 48) / 2 : 110;

    return Container(
      width: widget.isGrid ? (context.width() - 48) / 2 : itemSize,
      margin: EdgeInsets.only(top: 4),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            height: itemSize,
            width: itemSize,
            decoration: boxDecorationWithRoundedCorners(
              boxShape: BoxShape.circle,
              backgroundColor: appStore.isDarkMode ? context.cardColor : GreyLightColor,
            ),
            padding: EdgeInsets.all(12),
            child: ClipOval(
              child: cachedImage(
                widget.mProductCategoryModel!.productcategoryImage.validate(),
                fit: BoxFit.cover,
              ),
            ),
          ),
          12.height,
          Text(
            widget.mProductCategoryModel!.title!.validate(),
            style: primaryTextStyle(size: 14),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
          ).paddingSymmetric(horizontal: 8),
        ],
      ),
    ).onTap(() {
      ViewAllProductScreen(
        isCategory: true,
        title: widget.mProductCategoryModel!.title!.validate(),
        id: widget.mProductCategoryModel!.id!.validate(),
      ).launch(context);
    });
  }
}
