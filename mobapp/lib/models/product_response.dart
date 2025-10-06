
import '../models/pagination_model.dart';

class ProductResponse {
  Pagination? pagination;
  List<ProductModel>? data;

  ProductResponse({this.pagination, this.data});

  ProductResponse.fromJson(Map<String, dynamic> json) {
    pagination = json['pagination'] != null ? new Pagination.fromJson(json['pagination']) : null;
    if (json['data'] != null) {
      data = <ProductModel>[];
      json['data'].forEach((v) {
        data!.add(new ProductModel.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = new Map<String, dynamic>();
    if (this.pagination != null) {
      data['pagination'] = this.pagination!.toJson();
    }
    if (this.data != null) {
      data['data'] = this.data!.map((v) => v.toJson()).toList();
    }
    return data;
  }
}
class ProductModel {
  int? id;
  String? title;
  String? description;
  String? affiliateLink;
  num? price;
  num? finalPrice;
  num? discountPrice;
  num? discountPercent;
  bool? discountActive;
  int? productcategoryId;
  String? productcategoryTitle;
  String? featured;
  String? status;
  String? productImage;
  String? createdAt;
  String? updatedAt;
  bool? isFavourite;
  bool? isInCart;
  int? cartQuantity;

  ProductModel(
      {this.id,
        this.title,
        this.description,
        this.affiliateLink,
        this.price,
        this.finalPrice,
        this.discountPrice,
        this.discountPercent,
        this.discountActive,
        this.productcategoryId,
        this.productcategoryTitle,
        this.featured,
        this.status,
        this.productImage,
        this.createdAt,
        this.updatedAt,
        this.isFavourite,
        this.isInCart,
        this.cartQuantity});

  ProductModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = json['title'];
    description = json['description'];
    affiliateLink = json['affiliate_link'];
    price = json['price'];
    finalPrice = json['final_price'] != null ? num.tryParse(json['final_price'].toString()) : null;
    discountPrice = json['discount_price'] != null ? num.tryParse(json['discount_price'].toString()) : null;
    discountPercent = json['discount_percent'] != null ? num.tryParse(json['discount_percent'].toString()) : null;
    discountActive = json['discount_active'] == true || json['discount_active'] == 1;
    productcategoryId = json['productcategory_id'];
    if (json['productcategory_title'] != null) {
      productcategoryTitle = json['productcategory_title'];
    } else if (json['productcategory'] is Map<String, dynamic>) {
      productcategoryTitle = (json['productcategory'] as Map<String, dynamic>)['title'];
    }
    featured = json['featured'];
    status = json['status'];
    productImage = json['product_image'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    isFavourite = json['is_favourite'] == true || json['is_favourite'] == 1;
    isInCart = json['is_in_cart'] == true || json['is_in_cart'] == 1;
    cartQuantity = json['cart_quantity'] != null ? (json['cart_quantity'] as num).toInt() : 0;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = new Map<String, dynamic>();
    data['id'] = this.id;
    data['title'] = this.title;
    data['description'] = this.description;
    data['affiliate_link'] = this.affiliateLink;
    data['price'] = this.price;
    data['final_price'] = this.finalPrice;
    data['discount_price'] = this.discountPrice;
    data['discount_percent'] = this.discountPercent;
    data['discount_active'] = this.discountActive;
    data['productcategory_id'] = this.productcategoryId;
    data['productcategory_title'] = this.productcategoryTitle;
    data['featured'] = this.featured;
    data['status'] = this.status;
    data['product_image'] = this.productImage;
    data['created_at'] = this.createdAt;
    data['updated_at'] = this.updatedAt;
    data['is_favourite'] = this.isFavourite;
    data['is_in_cart'] = this.isInCart;
    data['cart_quantity'] = this.cartQuantity;
    return data;
  }
}
