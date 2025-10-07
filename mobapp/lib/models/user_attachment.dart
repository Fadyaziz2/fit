class UserAttachment {
  int? id;
  String? name;
  String? url;
  String? mimeType;
  int? size;

  UserAttachment({this.id, this.name, this.url, this.mimeType, this.size});

  UserAttachment.fromJson(Map<String, dynamic> json) {
    id = json['id'];
    name = json['name'];
    url = json['url'];
    mimeType = json['mime_type'];
    size = json['size'] != null ? int.tryParse(json['size'].toString()) : null;
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    data['url'] = url;
    data['mime_type'] = mimeType;
    data['size'] = size;
    return data;
  }
}
