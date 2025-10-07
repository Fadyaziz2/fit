class HealthConditionModel {
  int? id;
  String? name;
  String? startedAt;
  String? startedAtFormatted;

  HealthConditionModel({this.id, this.name, this.startedAt, this.startedAtFormatted});

  HealthConditionModel.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    name = json['name'];
    startedAt = json['started_at'];
    startedAtFormatted = json['started_at_formatted'];
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    data['started_at'] = startedAt;
    data['started_at_formatted'] = startedAtFormatted;
    return data;
  }
}
