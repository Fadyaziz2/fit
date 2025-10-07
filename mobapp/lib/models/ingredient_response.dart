import 'ingredient_model.dart';

class IngredientResponse {
  List<IngredientModel>? data;

  IngredientResponse({this.data});

  IngredientResponse.fromJson(Map<String, dynamic> json) {
    if (json['data'] != null) {
      data = <IngredientModel>[];
      json['data'].forEach((v) {
        data!.add(IngredientModel.fromJson(v));
      });
    }
  }
}
