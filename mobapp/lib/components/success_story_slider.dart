import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../extensions/decorations.dart';
import '../extensions/extension_util/context_extensions.dart';
import '../extensions/extension_util/int_extensions.dart';
import '../extensions/extension_util/string_extensions.dart';
import '../extensions/extension_util/widget_extensions.dart';
import '../extensions/text_styles.dart';
import '../main.dart';
import '../models/success_story_model.dart';

class SuccessStorySlider extends StatefulWidget {
  final List<SuccessStoryModel> stories;

  const SuccessStorySlider({super.key, required this.stories});

  @override
  State<SuccessStorySlider> createState() => _SuccessStorySliderState();
}

class _SuccessStorySliderState extends State<SuccessStorySlider> {
  late final PageController _pageController;
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    _pageController = PageController(viewportFraction: 0.9);
  }

  @override
  void didUpdateWidget(covariant SuccessStorySlider oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.stories != widget.stories) {
      _currentIndex = 0;
      if (_pageController.hasClients) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (_pageController.hasClients) {
            _pageController.jumpToPage(0);
          }
        });
      }
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.stories.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(languages.lblSuccessStories, style: boldTextStyle(size: 20)).paddingBottom(12),
        SizedBox(
          height: 260,
          child: PageView.builder(
            controller: _pageController,
            itemCount: widget.stories.length,
            onPageChanged: (index) {
              setState(() {
                _currentIndex = index;
              });
            },
            itemBuilder: (context, index) {
              final story = widget.stories[index];
              return AnimatedBuilder(
                animation: _pageController,
                builder: (context, child) {
                  double value = 1.0;
                  if (_pageController.position.haveDimensions) {
                    final double currentPage =
                        (_pageController.page ?? _pageController.initialPage.toDouble());
                    value = currentPage - index;
                    value = (1.0 - (value.abs() * 0.1)).clamp(0.9, 1.0);
                  }
                  return Center(
                    child: SizedBox(
                      height: Curves.easeOut.transform(value) * 250,
                      child: child,
                    ),
                  );
                },
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 8),
                  decoration: boxDecorationWithRoundedCorners(
                    borderRadius: radius(20),
                    backgroundColor: appStore.isDarkMode ? context.cardColor : Colors.white,
                    boxShadow: defaultBoxShadow(shadowColor: Colors.black12),
                  ),
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Row(
                          children: [
                            _buildStoryImage(story.beforeImage, languages.lblBefore),
                            12.width,
                            _buildStoryImage(story.afterImage, languages.lblAfter),
                          ],
                        ),
                      ),
                      16.height,
                      if (story.title.validate().isNotEmpty)
                        Text(
                          story.title.validate(),
                          style: boldTextStyle(size: 16),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      if (story.description.validate().isNotEmpty)
                        Text(
                          story.description.validate(),
                          style: secondaryTextStyle(),
                          maxLines: 3,
                          overflow: TextOverflow.ellipsis,
                        ).paddingTop(story.title.validate().isNotEmpty ? 6 : 0),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
        if (widget.stories.length > 1)
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(widget.stories.length, (index) {
              final isActive = index == _currentIndex;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 4, vertical: 12),
                height: 6,
                width: isActive ? 18 : 6,
                decoration: BoxDecoration(
                  color: isActive ? context.primaryColor : context.dividerColor.withOpacity(0.5),
                  borderRadius: radius(4),
                ),
              );
            }),
          ),
      ],
    );
  }

  Widget _buildStoryImage(String? url, String label) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: ClipRRect(
              borderRadius: radius(16),
              child: CachedNetworkImage(
                imageUrl: url.validate(),
                fit: BoxFit.cover,
                placeholder: (context, url) => Container(
                  color: context.dividerColor.withOpacity(0.2),
                ),
                errorWidget: (context, url, error) => Container(
                  color: context.dividerColor,
                  alignment: Alignment.center,
                  child: const Icon(Icons.broken_image_outlined),
                ),
              ),
            ),
          ),
          8.height,
          Text(label, style: secondaryTextStyle()),
        ],
      ),
    );
  }
}
