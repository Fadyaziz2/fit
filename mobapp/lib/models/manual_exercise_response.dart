import 'package:mighty_fitness/models/pagination_model.dart';
import 'package:mighty_fitness/utils/json_utils.dart';

class ManualExerciseResponse {
  Pagination? pagination;
  List<ManualExerciseModel>? data;

  ManualExerciseResponse({this.pagination, this.data});

  ManualExerciseResponse.fromJson(Map<String, dynamic> json) {
    pagination = json['pagination'] != null
        ? Pagination.fromJson(json['pagination'])
        : null;
    if (json['data'] != null) {
      data = <ManualExerciseModel>[];
      json['data'].forEach((v) {
        data!.add(ManualExerciseModel.fromJson(v));
      });
    }
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    if (pagination != null) {
      data['pagination'] = pagination!.toJson();
    }
    if (this.data != null) {
      data['data'] = this.data!.map((v) => v.toJson()).toList();
    }
    return data;
  }
}

class ManualExerciseModel {
  int? id;
  String? activity;
  double? duration;
  String? performedOn;
  String? createdAt;

  ManualExerciseModel({
    this.id,
    this.activity,
    this.duration,
    this.performedOn,
    this.createdAt,
  });

  ManualExerciseModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    activity = parseStringFromJson(json['activity']);
    duration = parseDouble(json['duration']);
    performedOn = parseStringFromJson(json['performed_on']);
    createdAt = parseStringFromJson(json['created_at']);
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['activity'] = activity;
    data['duration'] = duration;
    data['performed_on'] = performedOn;
    data['created_at'] = createdAt;
    return data;
  }
}
