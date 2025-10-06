import 'base_response.dart';
import 'product_response.dart';

class OrderResponse {
  List<OrderModel>? data;

  OrderResponse({this.data});

  OrderResponse.fromJson(Map<String, dynamic> json) {
    if (json['data'] != null) {
      data = <OrderModel>[];
      json['data'].forEach((v) {
        data!.add(OrderModel.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    if (this.data != null) {
      data['data'] = this.data!.map((v) => v.toJson()).toList();
    }
    return data;
  }
}

class CheckoutResponse extends FitnessBaseResponse {
  List<OrderModel>? data;

  CheckoutResponse({String? message, this.data}) : super(message: message);

  CheckoutResponse.fromJson(Map<String, dynamic> json) {
    message = json['message'];
    if (json['data'] != null) {
      data = <OrderModel>[];
      json['data'].forEach((v) {
        data!.add(OrderModel.fromJson(v));
      });
    }
  }

  @override
  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = super.toJson();
    if (this.data != null) {
      data['data'] = this.data!.map((v) => v.toJson()).toList();
    }
    return data;
  }
}

class OrderModel {
  int? id;
  int? productId;
  int? quantity;
  String? status;
  String? statusLabel;
  String? statusComment;
  double? unitPrice;
  double? totalPrice;
  String? paymentMethod;
  String? paymentMethodLabel;
  String? customerName;
  String? customerPhone;
  String? shippingAddress;
  String? customerNote;
  String? createdAt;
  String? createdAtFormatted;
  ProductModel? product;

  OrderModel({
    this.id,
    this.productId,
    this.quantity,
    this.status,
    this.statusLabel,
    this.statusComment,
    this.unitPrice,
    this.totalPrice,
    this.paymentMethod,
    this.paymentMethodLabel,
    this.customerName,
    this.customerPhone,
    this.shippingAddress,
    this.customerNote,
    this.createdAt,
    this.createdAtFormatted,
    this.product,
  });

  OrderModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    productId = json['product_id'];
    quantity = json['quantity'];
    status = json['status'];
    statusLabel = json['status_label'];
    statusComment = json['status_comment'];
    unitPrice = json['unit_price'] != null ? double.tryParse(json['unit_price'].toString()) : null;
    totalPrice = json['total_price'] != null ? double.tryParse(json['total_price'].toString()) : null;
    paymentMethod = json['payment_method'];
    paymentMethodLabel = json['payment_method_label'];
    customerName = json['customer_name'];
    customerPhone = json['customer_phone'];
    shippingAddress = json['shipping_address'];
    customerNote = json['customer_note'];
    createdAt = json['created_at'];
    createdAtFormatted = json['created_at_formatted'];
    product = json['product'] != null ? ProductModel.fromJson(json['product']) : null;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['product_id'] = productId;
    data['quantity'] = quantity;
    data['status'] = status;
    data['status_label'] = statusLabel;
    data['status_comment'] = statusComment;
    data['unit_price'] = unitPrice;
    data['total_price'] = totalPrice;
    data['payment_method'] = paymentMethod;
    data['payment_method_label'] = paymentMethodLabel;
    data['customer_name'] = customerName;
    data['customer_phone'] = customerPhone;
    data['shipping_address'] = shippingAddress;
    data['customer_note'] = customerNote;
    data['created_at'] = createdAt;
    data['created_at_formatted'] = createdAtFormatted;
    if (product != null) {
      data['product'] = product!.toJson();
    }
    return data;
  }
}
