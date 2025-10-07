class IngredientModel {
  int? id;
  String? title;
  String? description;
  double? protein;
  double? fat;
  double? carbs;
  String? image;

  IngredientModel({
    this.id,
    this.title,
    this.description,
    this.protein,
    this.fat,
    this.carbs,
    this.image,
  });

  IngredientModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = json['title'];
    description = json['description'];
    protein = json['protein'] != null ? double.tryParse(json['protein'].toString()) : null;
    fat = json['fat'] != null ? double.tryParse(json['fat'].toString()) : null;
    carbs = json['carbs'] != null ? double.tryParse(json['carbs'].toString()) : null;
    image = json['image'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['title'] = title;
    data['description'] = description;
    data['protein'] = protein;
    data['fat'] = fat;
    data['carbs'] = carbs;
    data['image'] = image;
    return data;
  }
}
