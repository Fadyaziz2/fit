import '../extensions/extension_util/string_extensions.dart';
import '../languageConfiguration/LanguageDefaultJson.dart';
import '../main.dart';

/// Safely converts dynamic values returned from the API into a String.
///
/// The backend can return raw strings, numbers, booleans, localized maps or
/// even lists for certain fields. This helper normalises all of these cases to
/// a readable string while respecting the selected application language.
String? parseStringFromJson(dynamic value) {
  if (value == null) return null;

  if (value is String) return value;
  if (value is num || value is bool) return value.toString();

  if (value is Map) {
    final normalizedSelectedLanguage =
        appStore.selectedLanguageCode.validate(value: defaultLanguageCode).toLowerCase();
    final possibleLanguageKeys = <String>{
      normalizedSelectedLanguage,
      defaultLanguageCode.toLowerCase(),
      'en',
    };

    for (final key in possibleLanguageKeys) {
      final match = value.entries.firstWhere(
        (entry) => entry.key.toString().toLowerCase() == key,
        orElse: () => MapEntry('', null),
      );

      if (match.value != null) {
        final matchValue = match.value;
        if (matchValue is String) return matchValue;
        if (matchValue is num || matchValue is bool) return matchValue.toString();
      }
    }

    for (final mapValue in value.values) {
      if (mapValue == null) continue;
      if (mapValue is String) return mapValue;
      if (mapValue is num || mapValue is bool) return mapValue.toString();
    }

    return value.toString();
  }

  if (value is Iterable) {
    final buffer = <String>[];
    for (final element in value) {
      final parsed = parseStringFromJson(element);
      final normalized = parsed.validate();
      if (normalized.isNotEmpty) buffer.add(normalized);
    }
    if (buffer.isNotEmpty) return buffer.join(', ');
  }

  return value.toString();
}

/// Safely converts dynamic json values to a [double].
///
/// Accepts numeric values and strings that can be parsed into a double.
/// Returns `null` when conversion isn't possible instead of throwing.
double? parseDouble(dynamic value) {
  if (value == null) return null;

  if (value is num) return value.toDouble();

  if (value is String) {
    final normalised = value.trim();
    if (normalised.isEmpty) return null;
    return double.tryParse(normalised);
  }

  return null;
}
