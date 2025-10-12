import 'base_response.dart';

class DiscountCodeResponse extends FitnessBaseResponse {
  DiscountSummary? data;

  DiscountCodeResponse({String? message, this.data}) : super(message: message);

  DiscountCodeResponse.fromJson(Map<String, dynamic> json) {
    message = json['message'];
    data = json['data'] != null ? DiscountSummary.fromJson(json['data']) : null;
  }
}

class DiscountSummary {
  String? code;
  String? name;
  String? discountType;
  num? discountValue;
  bool? isOneTimePerUser;
  int? maxRedemptions;
  int? remainingRedemptions;
  num? subtotalAmount;
  num? discountAmount;
  num? payableAmount;

  DiscountSummary({
    this.code,
    this.name,
    this.discountType,
    this.discountValue,
    this.isOneTimePerUser,
    this.maxRedemptions,
    this.remainingRedemptions,
    this.subtotalAmount,
    this.discountAmount,
    this.payableAmount,
  });

  DiscountSummary.fromJson(Map<String, dynamic> json) {
    code = json['code'];
    name = json['name'];
    discountType = json['discount_type'];
    discountValue = json['discount_value'] != null ? num.tryParse(json['discount_value'].toString()) : null;
    isOneTimePerUser = json['is_one_time_per_user'];
    maxRedemptions = json['max_redemptions'];
    remainingRedemptions = json['remaining_redemptions'];
    subtotalAmount = json['subtotal_amount'] != null ? num.tryParse(json['subtotal_amount'].toString()) : null;
    discountAmount = json['discount_amount'] != null ? num.tryParse(json['discount_amount'].toString()) : null;
    payableAmount = json['payable_amount'] != null ? num.tryParse(json['payable_amount'].toString()) : null;
  }
}
