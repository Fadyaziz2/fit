import 'package:mighty_fitness/utils/json_utils.dart';

class ExclusiveOfferResponse {
  ExclusiveOfferModel? data;

  ExclusiveOfferResponse({this.data});

  ExclusiveOfferResponse.fromJson(Map<String, dynamic> json) {
    if (json.containsKey('data') && json['data'] != null) {
      if (json['data'] is Map<String, dynamic>) {
        data = ExclusiveOfferModel.fromJson(json['data']);
      }
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> json = <String, dynamic>{};
    if (data != null) {
      json['data'] = data!.toJson();
    }
    return json;
  }
}

class ExclusiveOfferModel {
  int? id;
  String? title;
  String? description;
  String? buttonText;
  String? buttonUrl;
  String? status;
  String? image;
  String? activatedAt;
  String? createdAt;
  String? updatedAt;

  ExclusiveOfferModel({
    this.id,
    this.title,
    this.description,
    this.buttonText,
    this.buttonUrl,
    this.status,
    this.image,
    this.activatedAt,
    this.createdAt,
    this.updatedAt,
  });

  ExclusiveOfferModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = parseStringFromJson(json['title']);
    description = parseStringFromJson(json['description']);
    buttonText = parseStringFromJson(json['button_text']);
    buttonUrl = parseStringFromJson(json['button_url']);
    status = parseStringFromJson(json['status']);
    image = parseStringFromJson(json['image']);
    activatedAt = parseStringFromJson(json['activated_at']);
    createdAt = parseStringFromJson(json['created_at']);
    updatedAt = parseStringFromJson(json['updated_at']);
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['title'] = title;
    data['description'] = description;
    data['button_text'] = buttonText;
    data['button_url'] = buttonUrl;
    data['status'] = status;
    data['image'] = image;
    data['activated_at'] = activatedAt;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    return data;
  }
}
