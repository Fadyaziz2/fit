import 'pagination_model.dart';

class DietResponse {
  Pagination? pagination;
  List<DietModel>? data;

  DietResponse({this.pagination, this.data});

  DietResponse.fromJson(Map<String, dynamic> json) {
    pagination = json['pagination'] != null
        ? new Pagination.fromJson(json['pagination'])
        : null;
    if (json['data'] != null) {
      data = <DietModel>[];
      json['data'].forEach((v) {
        data!.add(new DietModel.fromJson(v));
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


class DietModel {
  int? id;
  String? title;
  String? calories;
  String? carbs;
  String? protein;
  String? fat;
  String? servings;
  String? totalTime;
  String? isFeatured;
  String? status;
  List<List<MealIngredientEntry>>? plan;
  List<List<MealIngredientEntry>>? customPlan;
  List<MealPlanDay>? mealPlan;
  bool? hasCustomPlan;
  String? description;
  String? dietImage;
  int? isPremium;
  int? categorydietId;
  String? categorydietTitle;
  String? createdAt;
  String? updatedAt;
  int? isFavourite;

  DietModel(
      {this.id,
        this.title,
        this.calories,
        this.carbs,
        this.protein,
        this.fat,
        this.servings,
        this.totalTime,
        this.isFeatured,
        this.status,
        this.plan,
        this.customPlan,
        this.mealPlan,
        this.hasCustomPlan,
        this.description,
        this.dietImage,
        this.isPremium,
        this.categorydietId,
        this.categorydietTitle,
        this.createdAt,
        this.updatedAt,
        this.isFavourite});

  DietModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = json['title'];
    calories = json['calories'];
    carbs = json['carbs'];
    protein = json['protein'];
    fat = json['fat'];
    servings = json['servings'];
    totalTime = json['total_time'];
    isFeatured = json['is_featured'];
    status = json['status'];
    plan = parseMealEntryMatrix(json['ingredients']);
    customPlan = parseMealEntryMatrix(json['custom_plan']);
    hasCustomPlan = json['has_custom_plan'] == true;
    mealPlan = parseMealPlanDays(json['meal_plan']);
    description = json['description'];
    dietImage = json['diet_image'];
    isPremium = json['is_premium'];
    categorydietId = json['categorydiet_id'];
    categorydietTitle = json['categorydiet_title'];
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
    isFavourite = json['is_favourite'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = new Map<String, dynamic>();
    data['id'] = this.id;
    data['title'] = this.title;
    data['calories'] = this.calories;
    data['carbs'] = this.carbs;
    data['protein'] = this.protein;
    data['fat'] = this.fat;
    data['servings'] = this.servings;
    data['total_time'] = this.totalTime;
    data['is_featured'] = this.isFeatured;
    data['status'] = this.status;
    if (this.plan != null) {
      data['ingredients'] = this
          .plan!
          .map((day) => day.map((entry) => entry.toJson()).toList())
          .toList();
    }
    if (this.customPlan != null) {
      data['custom_plan'] = this
          .customPlan!
          .map((day) => day.map((entry) => entry.toJson()).toList())
          .toList();
    }
    if (this.mealPlan != null) {
      data['meal_plan'] = this.mealPlan!.map((e) => e.toJson()).toList();
    }
    data['has_custom_plan'] = this.hasCustomPlan;
    data['description'] = this.description;
    data['diet_image'] = this.dietImage;
    data['is_premium'] = this.isPremium;
    data['categorydiet_id'] = this.categorydietId;
    data['categorydiet_title'] = this.categorydietTitle;
    data['created_at'] = this.createdAt;
    data['updated_at'] = this.updatedAt;
    data['is_favourite'] = this.isFavourite;
    return data;
  }
}

List<List<MealIngredientEntry>>? parseMealEntryMatrix(dynamic value) {
  if (value is List) {
    return value.map<List<MealIngredientEntry>>((day) {
      if (day is List) {
        return day
            .map<MealIngredientEntry>((meal) => MealIngredientEntry.fromJson(meal))
            .where((entry) => entry.id != null && entry.id! > 0)
            .toList();
      }

      return <MealIngredientEntry>[];
    }).toList();
  }

  return null;
}

List<MealPlanDay>? parseMealPlanDays(dynamic value) {
  if (value is List) {
    return value
        .map((day) => MealPlanDay.fromJson(_ensureMap(day)))
        .whereType<MealPlanDay>()
        .toList();
  }

  return null;
}

Map<String, dynamic>? _ensureMap(dynamic value) {
  if (value is Map<String, dynamic>) {
    return value;
  }

  if (value is Map) {
    return Map<String, dynamic>.from(value);
  }

  return null;
}

double _parseDouble(dynamic value) {
  if (value is num) {
    return value.toDouble();
  }

  if (value is String) {
    return double.tryParse(value) ?? 0;
  }

  return 0;
}

int _parseInt(dynamic value) {
  if (value is int) {
    return value;
  }

  if (value is num) {
    return value.toInt();
  }

  if (value is String) {
    return int.tryParse(value) ?? 0;
  }

  return 0;
}

class MealIngredientEntry {
  int? id;
  double quantity;

  MealIngredientEntry({this.id, this.quantity = 1});

  factory MealIngredientEntry.fromJson(dynamic json) {
    if (json is Map) {
      final map = _ensureMap(json) ?? {};

      return MealIngredientEntry(
        id: _parseInt(map['id'] ?? map['ingredient_id'] ?? map['ingredient']),
        quantity: sanitizeQuantityValue(map['quantity'] ?? map['qty'] ?? map['amount']),
      );
    }

    if (json is num || json is String) {
      return MealIngredientEntry(
        id: _parseInt(json),
        quantity: 1,
      );
    }

    return MealIngredientEntry(id: null, quantity: 1);
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'quantity': quantity,
    };
  }

  static double sanitizeQuantityValue(dynamic value) {
    if (value == null) {
      return 1;
    }

    if (value is String) {
      final normalized = value.replaceAll(',', '.');
      final parsed = double.tryParse(normalized) ?? 1;
      return parsed <= 0 ? 1 : parsed;
    }

    if (value is num) {
      final parsed = value.toDouble();
      return parsed <= 0 ? 1 : parsed;
    }

    return 1;
  }
}

class MealPlanTotals {
  double protein;
  double carbs;
  double fat;
  double calories;

  MealPlanTotals({
    this.protein = 0,
    this.carbs = 0,
    this.fat = 0,
    this.calories = 0,
  });

  factory MealPlanTotals.fromJson(Map<String, dynamic>? json) {
    json ??= {};

    return MealPlanTotals(
      protein: _parseDouble(json['protein']),
      carbs: _parseDouble(json['carbs']),
      fat: _parseDouble(json['fat']),
      calories: _parseDouble(json['calories']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'protein': protein,
      'carbs': carbs,
      'fat': fat,
      'calories': calories,
    };
  }
}

class MealPlanIngredientDetail {
  int? id;
  String? title;
  double quantity;
  double protein;
  double carbs;
  double fat;
  double calories;
  String? image;
  String? description;

  MealPlanIngredientDetail({
    this.id,
    this.title,
    this.quantity = 1,
    this.protein = 0,
    this.carbs = 0,
    this.fat = 0,
    this.calories = 0,
    this.image,
    this.description,
  });

  factory MealPlanIngredientDetail.fromJson(Map<String, dynamic>? json) {
    json ??= {};

    return MealPlanIngredientDetail(
      id: _parseInt(json['id']),
      title: json['title']?.toString(),
      quantity: _parseDouble(json['quantity']),
      protein: _parseDouble(json['protein']),
      carbs: _parseDouble(json['carbs']),
      fat: _parseDouble(json['fat']),
      calories: _parseDouble(json['calories']),
      image: json['image']?.toString(),
      description: json['description']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'quantity': quantity,
      'protein': protein,
      'carbs': carbs,
      'fat': fat,
      'calories': calories,
      'image': image,
      'description': description,
    };
  }
}

class MealPlanMeal {
  int mealNumber;
  List<MealPlanIngredientDetail> ingredients;
  MealPlanTotals totals;

  MealPlanMeal({required this.mealNumber, required this.ingredients, required this.totals});

  factory MealPlanMeal.fromJson(Map<String, dynamic>? json) {
    json ??= {};

    final ingredientsJson = json['ingredients'] as List? ?? [];

    return MealPlanMeal(
      mealNumber: _parseInt(json['meal_number']),
      ingredients: ingredientsJson
          .map((ingredient) => MealPlanIngredientDetail.fromJson(_ensureMap(ingredient)))
          .whereType<MealPlanIngredientDetail>()
          .toList(),
      totals: MealPlanTotals.fromJson(_ensureMap(json['totals'])),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'meal_number': mealNumber,
      'ingredients': ingredients.map((e) => e.toJson()).toList(),
      'totals': totals.toJson(),
    };
  }
}

class MealPlanDay {
  int dayNumber;
  List<MealPlanMeal> meals;
  MealPlanTotals totals;

  MealPlanDay({required this.dayNumber, required this.meals, required this.totals});

  factory MealPlanDay.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return MealPlanDay(dayNumber: 0, meals: [], totals: MealPlanTotals());
    }

    final mealsJson = json['meals'] as List? ?? [];

    return MealPlanDay(
      dayNumber: _parseInt(json['day_number']),
      meals: mealsJson
          .map((meal) => MealPlanMeal.fromJson(_ensureMap(meal)))
          .whereType<MealPlanMeal>()
          .toList(),
      totals: MealPlanTotals.fromJson(_ensureMap(json['totals'])),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'day_number': dayNumber,
      'meals': meals.map((e) => e.toJson()).toList(),
      'totals': totals.toJson(),
    };
  }
}
