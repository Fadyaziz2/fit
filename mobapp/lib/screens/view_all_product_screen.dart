import 'package:flutter/material.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../screens/no_data_screen.dart';
import '../components/adMob_component.dart';
import '../components/product_component.dart';
import '../extensions/animatedList/animated_wrap.dart';
import '../extensions/loader_widget.dart';
import '../extensions/widgets.dart';
import '../main.dart';
import '../models/product_category_response.dart';
import '../models/product_response.dart';
import '../network/rest_api.dart';

class ViewAllProductScreen extends StatefulWidget {
  final bool? isCategory;
  final int? id;
  final String? title;
  final bool showDiscountOnly;

  const ViewAllProductScreen({super.key, this.isCategory = false, this.title, this.id, this.showDiscountOnly = false});

  @override
  State<ViewAllProductScreen> createState() => _ViewAllProductScreenState();
}

class _ViewAllProductScreenState extends State<ViewAllProductScreen> {
  ScrollController scrollController = ScrollController();

  List<ProductModel> mProductList = [];

  ProductCategoryModel? mCategoryDietModel;

  int page = 1;
  int? numPage;

  bool isLastPage = false;

  @override
  void initState() {
    super.initState();
    init();
    scrollController.addListener(() {
      if (scrollController.position.pixels == scrollController.position.maxScrollExtent && !appStore.isLoading) {
        if (page < numPage!) {
          page++;
          init();
        }
      }
    });
  }

  void init() async {
    getAllProductData();
  }

  Future<void> getAllProductData() async {
    appStore.setLoading(true);
    await getProductApi(page: page, isCategory: widget.isCategory, productId: widget.id.validate()).then((value) async {
      appStore.setLoading(false);
      numPage = value.pagination?.totalPages;
      isLastPage = false;
      if (page == 1) {
        mProductList.clear();
      }

      List<ProductModel> fetchedProducts = value.data ?? [];
      List<ProductModel> productsToAdd;

      if (widget.showDiscountOnly) {
        productsToAdd = fetchedProducts.where((product) => _isProductDiscounted(product)).toList();
      } else {
        productsToAdd = fetchedProducts;
      }

      mProductList.addAll(productsToAdd);
      setState(() {});

      if (widget.showDiscountOnly && productsToAdd.isEmpty) {
        int totalPages = numPage ?? page;
        if (page < totalPages) {
          page++;
          await getAllProductData();
        }
      }
    }).catchError((e) {
      isLastPage = true;
      appStore.setLoading(false);
      setState(() {});
    });
  }

  bool _isProductDiscounted(ProductModel product) {
    final bool hasActiveFlag = product.discountActive == true;
    final bool hasDiscountPrice = (product.discountPrice ?? 0) > 0;
    final bool hasDiscountPercent = (product.discountPercent ?? 0) > 0;
    final bool hasLowerFinalPrice =
        product.price != null && product.finalPrice != null && product.finalPrice! < product.price!;

    return hasActiveFlag || hasDiscountPrice || hasDiscountPercent || hasLowerFinalPrice;
  }

  @override
  void dispose() {
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: appBarWidget(
        widget.isCategory == true ? widget.title.toString() : (widget.title ?? languages.lblProductList),
        elevation: 0,
        context: context,
      ),
      body: Stack(
        children: [
          mProductList.isNotEmpty
              ? SingleChildScrollView(
                  controller: scrollController,
                  physics: BouncingScrollPhysics(),
                  padding: EdgeInsets.only(bottom: 16, top: 4),
                  child: AnimatedWrap(
                    runSpacing: 16,
                    spacing: 16,
                    children: List.generate(mProductList.length, (index) {
                      return ProductComponent(
                        mProductModel: mProductList[index],
                        onCall: () {
                          getAllProductData();
                          setState(() {});
                        },
                      );
                    }),
                  ).paddingSymmetric(horizontal: 16),
                )
              : NoDataScreen(mTitle: languages.lblResultNoFound).center().visible(!appStore.isLoading),
          Loader().center().visible(appStore.isLoading)
        ],
      ),
      bottomNavigationBar: userStore.adsBannerDetailShowBannerOnProduct == 1 && userStore.isSubscribe == 0 ? showBannerAds(context) : SizedBox(),
    );
  }
}
