class ClinicBranch {
  final int id;
  final String name;
  final String? phone;
  final String? email;
  final String? address;

  ClinicBranch({required this.id, required this.name, this.phone, this.email, this.address});

  factory ClinicBranch.fromJson(Map<String, dynamic> json) {
    return ClinicBranch(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      phone: json['phone'],
      email: json['email'],
      address: json['address'],
    );
  }
}

class ClinicSlot {
  final String time;
  final bool isAvailable;

  ClinicSlot({required this.time, required this.isAvailable});

  factory ClinicSlot.fromJson(Map<String, dynamic> json) {
    return ClinicSlot(
      time: json['time'] ?? '',
      isAvailable: json['is_available'] ?? false,
    );
  }
}

class ClinicAppointmentSummary {
  final int id;
  final String date;
  final String time;
  final String status;
  final String type;
  final String? specialistName;
  final String? branchName;

  ClinicAppointmentSummary({
    required this.id,
    required this.date,
    required this.time,
    required this.status,
    required this.type,
    this.specialistName,
    this.branchName,
  });

  factory ClinicAppointmentSummary.fromJson(Map<String, dynamic> json) {
    return ClinicAppointmentSummary(
      id: json['id'] ?? 0,
      date: json['date'] ?? '',
      time: json['time'] ?? '',
      status: json['status'] ?? '',
      type: json['type'] ?? '',
      specialistName: json['specialist'] != null ? json['specialist']['name'] : null,
      branchName: json['branch'] != null ? json['branch']['name'] : null,
    );
  }
}

class ClinicAppointmentsResponse {
  final List<ClinicAppointmentSummary> upcoming;
  final List<ClinicAppointmentSummary> past;

  ClinicAppointmentsResponse({required this.upcoming, required this.past});

  factory ClinicAppointmentsResponse.fromJson(Map<String, dynamic> json) {
    final upcoming = (json['upcoming'] as List? ?? [])
        .map((e) => ClinicAppointmentSummary.fromJson(e))
        .toList();
    final past = (json['past'] as List? ?? [])
        .map((e) => ClinicAppointmentSummary.fromJson(e))
        .toList();
    return ClinicAppointmentsResponse(upcoming: upcoming, past: past);
  }
}
