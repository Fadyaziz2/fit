import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/string_extensions.dart';
import 'package:mighty_fitness/extensions/text_styles.dart';
import 'package:mighty_fitness/models/exclusive_offer_response.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_images.dart';
import 'package:url_launcher/url_launcher.dart';

Future<void> showExclusiveOfferDialog(BuildContext context, ExclusiveOfferModel offer) async {
  if (offer.title.validate().isEmpty && offer.description.validate().isEmpty && offer.image.validate().isEmpty) {
    return;
  }

  return showDialog(
    context: context,
    barrierDismissible: true,
    builder: (ctx) {
      final textTheme = Theme.of(ctx).textTheme;
      final buttonTextStyle = textTheme.labelLarge ?? const TextStyle(fontSize: 16);

      return Dialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Align(
                  alignment: Alignment.topRight,
                  child: InkWell(
                    onTap: () => Navigator.of(ctx).pop(),
                    borderRadius: BorderRadius.circular(20),
                    child: const Padding(
                      padding: EdgeInsets.all(4.0),
                      child: Icon(Icons.close, size: 20),
                    ),
                  ),
                ),
                if (offer.image.validate().isNotEmpty)
                  ClipRRect(
                    borderRadius: BorderRadius.circular(16),
                    child: CachedNetworkImage(
                      imageUrl: offer.image.validate(),
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Image.asset(
                        ic_placeholder,
                        fit: BoxFit.cover,
                      ),
                      errorWidget: (context, url, error) => Image.asset(
                        ic_placeholder,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                const SizedBox(height: 16),
                if (offer.title.validate().isNotEmpty)
                  Text(
                    offer.title.validate(),
                    style: textTheme.headline6?.copyWith(fontWeight: FontWeight.w600),
                    textAlign: TextAlign.center,
                  ),
                if (offer.description.validate().isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text(
                    offer.description.validate(),
                    style: primaryTextStyle(size: 14, color: context.bodyTextColor),
                    textAlign: TextAlign.center,
                  ),
                ],
                if (offer.buttonUrl.validate().isNotEmpty && offer.buttonText.validate().isNotEmpty) ...[
                  const SizedBox(height: 20),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      backgroundColor: primaryColor,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    onPressed: () async {
                      final url = offer.buttonUrl.validate();
                      if (await canLaunchUrl(Uri.parse(url))) {
                        await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
                      }
                      if (ctx.mounted) {
                        Navigator.of(ctx).pop();
                      }
                    },
                    child: Text(
                      offer.buttonText.validate(),
                      style: buttonTextStyle.copyWith(color: Colors.white, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      );
    },
  );
}
