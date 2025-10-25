class LiveChatSender {
  final int? id;
  final String? name;
  final String? profileImage;

  LiveChatSender({this.id, this.name, this.profileImage});

  factory LiveChatSender.fromJson(Map<String, dynamic> json) {
    return LiveChatSender(
      id: json['id'],
      name: json['name'],
      profileImage: json['profile_image'],
    );
  }
}

class LiveChatMessage {
  final int id;
  final int threadId;
  final int senderId;
  final String senderType;
  final String message;
  final DateTime createdAt;
  final DateTime? readAt;
  final LiveChatSender? sender;

  LiveChatMessage({
    required this.id,
    required this.threadId,
    required this.senderId,
    required this.senderType,
    required this.message,
    required this.createdAt,
    this.readAt,
    this.sender,
  });

  factory LiveChatMessage.fromJson(Map<String, dynamic> json) {
    return LiveChatMessage(
      id: json['id'],
      threadId: json['thread_id'],
      senderId: json['sender_id'],
      senderType: json['sender_type'],
      message: json['message'] ?? '',
      createdAt: DateTime.tryParse(json['created_at'] ?? '') ?? DateTime.now(),
      readAt: json['read_at'] != null ? DateTime.tryParse(json['read_at']) : null,
      sender: json['sender'] != null ? LiveChatSender.fromJson(json['sender']) : null,
    );
  }
}

class LiveChatThread {
  final int id;
  final int userId;
  final int? assignedTo;
  final DateTime? lastMessageAt;
  final List<LiveChatMessage> messages;

  LiveChatThread({
    required this.id,
    required this.userId,
    this.assignedTo,
    this.lastMessageAt,
    this.messages = const [],
  });

  factory LiveChatThread.fromJson(Map<String, dynamic> json) {
    return LiveChatThread(
      id: json['id'],
      userId: json['user_id'],
      assignedTo: json['assigned_to'],
      lastMessageAt: json['last_message_at'] != null
          ? DateTime.tryParse(json['last_message_at'])
          : null,
      messages: json['messages'] != null
          ? List<LiveChatMessage>.from(
              (json['messages'] as List).map((e) => LiveChatMessage.fromJson(e)))
          : [],
    );
  }
}
