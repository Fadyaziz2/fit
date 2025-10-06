import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:mighty_fitness/main.dart';
import 'package:mighty_fitness/models/banner_model.dart';
import 'package:mighty_fitness/utils/app_common.dart';
import 'package:nb_utils/nb_utils.dart';

class ProductBannerCarousel extends StatefulWidget {
  final List<BannerModel> banners;

  const ProductBannerCarousel({super.key, required this.banners});

  @override
  State<ProductBannerCarousel> createState() => _ProductBannerCarouselState();
}

class _ProductBannerCarouselState extends State<ProductBannerCarousel> {
  late final PageController _pageController;
  Timer? _autoPlayTimer;
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    _restartTimer();
  }

  @override
  void didUpdateWidget(ProductBannerCarousel oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.banners != widget.banners) {
      _currentIndex = 0;
      if (_pageController.hasClients) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (_pageController.hasClients) {
            _pageController.jumpToPage(0);
          }
        });
      }
      _restartTimer();
    }
  }

  void _restartTimer() {
    _autoPlayTimer?.cancel();
    if (widget.banners.length <= 1) return;

    _autoPlayTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
      if (!mounted || !_pageController.hasClients) return;
      _currentIndex = (_currentIndex + 1) % widget.banners.length;
      _pageController.animateToPage(
        _currentIndex,
        duration: const Duration(milliseconds: 500),
        curve: Curves.easeInOut,
      );
      setState(() {});
    });
  }

  @override
  void dispose() {
    _autoPlayTimer?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _onBannerTap(BannerModel banner) async {
    if (banner.redirectUrl.validate().isEmpty) return;
    await launchUrls(banner.redirectUrl.validate());
  }

  @override
  Widget build(BuildContext context) {
    if (widget.banners.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          height: 190,
          child: PageView.builder(
            controller: _pageController,
            itemCount: widget.banners.length,
            onPageChanged: (index) {
              setState(() {
                _currentIndex = index;
              });
            },
            itemBuilder: (context, index) {
              final banner = widget.banners[index];
              return GestureDetector(
                onTap: () => _onBannerTap(banner),
                child: Container(
                  margin: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: boxDecorationWithRoundedCorners(
                    borderRadius: radius(20),
                    backgroundColor: appStore.isDarkMode ? context.cardColor : Colors.white,
                    boxShadow: defaultBoxShadow(shadowColor: Colors.black12),
                  ),
                  child: ClipRRect(
                    borderRadius: radius(20),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        CachedNetworkImage(
                          imageUrl: banner.bannerImage.validate(),
                          fit: BoxFit.cover,
                          placeholder: (context, url) => const SizedBox(),
                          errorWidget: (context, url, error) => Container(
                            color: context.dividerColor,
                            alignment: Alignment.center,
                            child: const Icon(Icons.image_not_supported_outlined),
                          ),
                        ),
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [
                                Colors.black.withOpacity(0.6),
                                Colors.transparent,
                              ],
                            ),
                          ),
                        ),
                        Positioned(
                          left: 20,
                          right: 20,
                          bottom: 20,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (banner.title.validate().isNotEmpty)
                                Text(
                                  banner.title.validate(),
                                  style: boldTextStyle(color: Colors.white, size: 20),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              if (banner.subtitle.validate().isNotEmpty)
                                Text(
                                  banner.subtitle.validate(),
                                  style: secondaryTextStyle(color: Colors.white70),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                ).paddingTop(banner.title.validate().isNotEmpty ? 8 : 0),
                              if (banner.buttonText.validate().isNotEmpty)
                                Container(
                                  margin: const EdgeInsets.only(top: 12),
                                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.92),
                                    borderRadius: radius(30),
                                  ),
                                  child: Text(
                                    banner.buttonText.validate(),
                                    style: boldTextStyle(color: Colors.black87),
                                  ),
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
        if (widget.banners.length > 1)
          12.height,
        if (widget.banners.length > 1)
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(widget.banners.length, (index) {
              final isActive = index == _currentIndex;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 4),
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
}
