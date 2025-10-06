import 'package:mighty_fitness/utils/json_utils.dart';

class BannerModel {
  int? id;
  String? title;
  String? subtitle;
  String? buttonText;
  String? redirectUrl;
  int? displayOrder;
  String? status;
  String? bannerImage;
  String? createdAt;
  String? updatedAt;

  BannerModel({
    this.id,
    this.title,
    this.subtitle,
    this.buttonText,
    this.redirectUrl,
    this.displayOrder,
    this.status,
    this.bannerImage,
    this.createdAt,
    this.updatedAt,
  });

  BannerModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = parseStringFromJson(json['title']);
    subtitle = parseStringFromJson(json['subtitle']);
    buttonText = parseStringFromJson(json['button_text']);
    redirectUrl = parseStringFromJson(json['redirect_url']);
    if (json['display_order'] is int) {
      displayOrder = json['display_order'];
    } else if (json['display_order'] != null) {
      displayOrder = int.tryParse(json['display_order'].toString());
    }
    status = parseStringFromJson(json['status']);
    bannerImage = parseStringFromJson(json['banner_image']);
    createdAt = parseStringFromJson(json['created_at']);
    updatedAt = parseStringFromJson(json['updated_at']);
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['title'] = title;
    data['subtitle'] = subtitle;
    data['button_text'] = buttonText;
    data['redirect_url'] = redirectUrl;
    data['display_order'] = displayOrder;
    data['status'] = status;
    data['banner_image'] = bannerImage;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    return data;
  }
}
