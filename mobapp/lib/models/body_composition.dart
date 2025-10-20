class BodyComposition {
  int? id;
  String? recordedAt;
  String? recordedAtFormatted;
  double? fatWeight;
  double? waterWeight;
  double? muscleWeight;
  String? createdAt;
  String? updatedAt;

  BodyComposition({
    this.id,
    this.recordedAt,
    this.recordedAtFormatted,
    this.fatWeight,
    this.waterWeight,
    this.muscleWeight,
    this.createdAt,
    this.updatedAt,
  });

  BodyComposition.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    recordedAt = json['recorded_at'];
    recordedAtFormatted = json['recorded_at_formatted'];
    fatWeight = _tryParseDouble(json['fat_weight']);
    waterWeight = _tryParseDouble(json['water_weight']);
    muscleWeight = _tryParseDouble(json['muscle_weight']);
    createdAt = json['created_at'];
    updatedAt = json['updated_at'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['recorded_at'] = recordedAt;
    data['recorded_at_formatted'] = recordedAtFormatted;
    data['fat_weight'] = fatWeight;
    data['water_weight'] = waterWeight;
    data['muscle_weight'] = muscleWeight;
    data['created_at'] = createdAt;
    data['updated_at'] = updatedAt;
    return data;
  }

  double? _tryParseDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString());
  }
}
