import '../utils/json_utils.dart';

class SuccessStoryModel {
  int? id;
  String? title;
  String? description;
  int? displayOrder;
  String? status;
  String? beforeImage;
  String? afterImage;
  String? createdAt;
  String? updatedAt;

  SuccessStoryModel({
    this.id,
    this.title,
    this.description,
    this.displayOrder,
    this.status,
    this.beforeImage,
    this.afterImage,
    this.createdAt,
    this.updatedAt,
  });

  SuccessStoryModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = parseStringFromJson(json['title']);
    description = parseStringFromJson(json['description']);
    displayOrder = json['display_order'];
    status = parseStringFromJson(json['status']);
    beforeImage = parseStringFromJson(json['before_image']);
    afterImage = parseStringFromJson(json['after_image']);
    createdAt = parseStringFromJson(json['created_at']);
    updatedAt = parseStringFromJson(json['updated_at']);
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['title'] = title;
    data['description'] = description;
    data['display_order'] = displayOrder;
    data['status'] = status;
    data['before_image'] = beforeImage;
    data['after_image'] = afterImage;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    return data;
  }
}
