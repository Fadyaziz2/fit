import 'product_response.dart';

class CartResponse {
  List<CartItemModel>? data;
  CartSummary? summary;

  CartResponse({this.data, this.summary});

  CartResponse.fromJson(Map<String, dynamic> json) {
    if (json['data'] != null) {
      data = <CartItemModel>[];
      json['data'].forEach((v) {
        data!.add(CartItemModel.fromJson(v));
      });
    }
    summary = json['summary'] != null ? CartSummary.fromJson(json['summary']) : null;
  }
}

class CartItemModel {
  int? id;
  int? quantity;
  num? unitPrice;
  num? unitDiscount;
  num? totalPrice;
  ProductModel? product;
  String? createdAt;
  String? updatedAt;

  CartItemModel({this.id, this.quantity, this.unitPrice, this.unitDiscount, this.totalPrice, this.product, this.createdAt, this.updatedAt});

  CartItemModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    quantity = json['quantity'];
    unitPrice = json['unit_price'] != null ? num.tryParse(json['unit_price'].toString()) : null;
    unitDiscount = json['unit_discount'] != null ? num.tryParse(json['unit_discount'].toString()) : null;
    totalPrice = json['total_price'] != null ? num.tryParse(json['total_price'].toString()) : null;
    product = json['product'] != null ? ProductModel.fromJson(json['product']) : null;
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['quantity'] = quantity;
    data['unit_price'] = unitPrice;
    data['unit_discount'] = unitDiscount;
    data['total_price'] = totalPrice;
    if (product != null) {
      data['product'] = product!.toJson();
    }
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    return data;
  }
}

class CartSummary {
  int? totalItems;
  num? totalAmount;

  CartSummary({this.totalItems, this.totalAmount});

  CartSummary.fromJson(Map<String, dynamic> json) {
    totalItems = json['total_items'];
    totalAmount = json['total_amount'] != null ? num.tryParse(json['total_amount'].toString()) : null;
  }
}
