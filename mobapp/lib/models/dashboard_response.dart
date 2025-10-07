import '../models/workout_detail_response.dart';
import '../utils/json_utils.dart';

import 'body_part_response.dart';
import 'diet_response.dart';
import 'equipment_response.dart';
import 'exercise_response.dart';
import 'level_response.dart';
import 'product_response.dart';
import 'banner_model.dart';
import 'success_story_model.dart';

class DashboardResponse {
  List<BodyPartModel>? bodypart;
  List<LevelModel>? level;
  List<EquipmentModel>? equipment;
  List<ExerciseModel>? exercise;
  List<Diet>? diet;
  List<Workouttype>? workouttype;
  List<WorkoutDetailModel>? workout;
  List<Diet>? featuredDiet;
  List<ProductModel>? featuredProducts;
  List<BannerModel>? productBanners;
  List<SuccessStoryModel>? successStories;

  DashboardResponse({this.bodypart, this.level, this.equipment, this.exercise, this.diet, this.workouttype, this.workout, this.featuredDiet, this.featuredProducts, this.productBanners, this.successStories});

  DashboardResponse.fromJson(Map<String, dynamic> json) {
    if (json['bodypart'] != null) {
      bodypart = <BodyPartModel>[];
      json['bodypart'].forEach((v) {
        bodypart!.add(new BodyPartModel.fromJson(v));
      });
    }
    if (json['level'] != null) {
      level = <LevelModel>[];
      json['level'].forEach((v) {
        level!.add(new LevelModel.fromJson(v));
      });
    }
    if (json['equipment'] != null) {
      equipment = <EquipmentModel>[];
      json['equipment'].forEach((v) {
        equipment!.add(new EquipmentModel.fromJson(v));
      });
    }
    if (json['exercise'] != null) {
      exercise = <ExerciseModel>[];
      json['exercise'].forEach((v) {
        exercise!.add(new ExerciseModel.fromJson(v));
      });
    }
    // if (json['diet'] != null) {
    //   diet = <Diet>[];
    //   json['diet'].forEach((v) {
    //     diet!.add(new Diet.fromJson(v));
    //   });
    // }
    if (json['workouttype'] != null) {
      workouttype = <Workouttype>[];
      json['workouttype'].forEach((v) {
        workouttype!.add(new Workouttype.fromJson(v));
      });
    }
    if (json['workout'] != null) {
      workout = <WorkoutDetailModel>[];
      json['workout'].forEach((v) {
        workout!.add(new WorkoutDetailModel.fromJson(v));
      });
    }
    // if (json['featured_diet'] != null) {
    //   featuredDiet = <Diet>[];
    //   json['featured_diet'].forEach((v) {
    //     featuredDiet!.add(new Diet.fromJson(v));
    //   });
    // }
    if (json['featured_products'] != null) {
      featuredProducts = <ProductModel>[];
      json['featured_products'].forEach((v) {
        featuredProducts!.add(ProductModel.fromJson(v));
      });
    }
    if (json['product_banners'] != null) {
      productBanners = <BannerModel>[];
      json['product_banners'].forEach((v) {
        productBanners!.add(BannerModel.fromJson(v));
      });
    }
    if (json['success_stories'] != null) {
      successStories = <SuccessStoryModel>[];
      json['success_stories'].forEach((v) {
        successStories!.add(SuccessStoryModel.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = new Map<String, dynamic>();
    if (this.bodypart != null) {
      data['bodypart'] = this.bodypart!.map((v) => v.toJson()).toList();
    }
    if (this.level != null) {
      data['level'] = this.level!.map((v) => v.toJson()).toList();
    }
    if (this.equipment != null) {
      data['equipment'] = this.equipment!.map((v) => v.toJson()).toList();
    }
    if (this.exercise != null) {
      data['exercise'] = this.exercise!.map((v) => v.toJson()).toList();
    }
    if (this.diet != null) {
      data['diet'] = this.diet!.map((v) => v.toJson()).toList();
    }
    if (this.workouttype != null) {
      data['workouttype'] = this.workouttype!.map((v) => v.toJson()).toList();
    }
    if (this.workout != null) {
      data['workout'] = this.workout!.map((v) => v.toJson()).toList();
    }
    if (this.featuredDiet != null) {
      data['featured_diet'] = this.featuredDiet!.map((v) => v.toJson()).toList();
    }
    if (this.featuredProducts != null) {
      data['featured_products'] = this.featuredProducts!.map((v) => v.toJson()).toList();
    }
    if (this.productBanners != null) {
      data['product_banners'] = this.productBanners!.map((v) => v.toJson()).toList();
    }
    if (this.successStories != null) {
      data['success_stories'] = this.successStories!.map((v) => v.toJson()).toList();
    }
    return data;
  }
}

class Diet {
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

  Diet(
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

  Diet.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = parseStringFromJson(json['title']);
    calories = parseStringFromJson(json['calories']);
    carbs = parseStringFromJson(json['carbs']);
    protein = parseStringFromJson(json['protein']);
    fat = parseStringFromJson(json['fat']);
    servings = parseStringFromJson(json['servings']);
    totalTime = parseStringFromJson(json['total_time']);
    isFeatured = parseStringFromJson(json['is_featured']);
    status = parseStringFromJson(json['status']);
    plan = parseMealEntryMatrix(json['ingredients']);
    customPlan = parseMealEntryMatrix(json['custom_plan']);
    hasCustomPlan = json['has_custom_plan'] == true;
    mealPlan = parseMealPlanDays(json['meal_plan']);
    description = parseStringFromJson(json['description']);
    dietImage = parseStringFromJson(json['diet_image']);
    isPremium = json['is_premium'];
    categorydietId = json['categorydiet_id'];
    categorydietTitle = parseStringFromJson(json['categorydiet_title']);
    createdAt = parseStringFromJson(json['created_at']);
    updatedAt = parseStringFromJson(json['updated_at']);
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

class Workouttype {
  int? id;
  String? title;
  String? status;
  String? createdAt;
  String? updatedAt;

  Workouttype({this.id, this.title, this.status, this.createdAt, this.updatedAt});

  Workouttype.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    title = parseStringFromJson(json['title']);
    status = parseStringFromJson(json['status']);
    createdAt = parseStringFromJson(json['created_at']);
    updatedAt = parseStringFromJson(json['updated_at']);
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = new Map<String, dynamic>();
    data['id'] = this.id;
    data['title'] = this.title;
    data['status'] = this.status;
    data['created_at'] = this.createdAt;
    data['updated_at'] = this.updatedAt;
    return data;
  }
}

