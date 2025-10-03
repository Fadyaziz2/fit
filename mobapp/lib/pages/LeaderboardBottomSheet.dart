import 'package:flutter/material.dart';
import 'package:mighty_fitness/extensions/extension_util/int_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/widget_extensions.dart';
import 'package:mighty_fitness/extensions/text_styles.dart';
import 'package:mighty_fitness/main.dart';
import 'package:mighty_fitness/models/GameResponse.dart';
import 'package:mighty_fitness/network/rest_api.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_common.dart';

class LeaderboardBottomSheet extends StatefulWidget {
  const LeaderboardBottomSheet({Key? key}) : super(key: key);

  @override
  State<LeaderboardBottomSheet> createState() => _LeaderboardBottomSheetState();
}

class _LeaderboardBottomSheetState extends State<LeaderboardBottomSheet> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;
  ScrollController scrollController = ScrollController();
  int page = 1;
  int? numPage;
  List<GameResponseData> mRecordList = [];

  bool isLastPage = false;

  @override
  void initState() {
    super.initState();
    getExerciseData();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 500),
      vsync: this,
    );
    _animation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOut,
    );
    _controller.forward();
    scrollController.addListener(() {
      if (scrollController.position.pixels == scrollController.position.maxScrollExtent && !appStore.isLoading) {
        if (page < numPage!) {
          page++;
          getExerciseData();
        }
      }
    });
  }


  Future<void> getExerciseData() async {
    appStore.setLoading(true);
    await getGamerRecord(page: page).then((value) {
      appStore.setLoading(false);
      numPage = value.pagination!.totalPages;
      isLastPage = false;
      if (page == 1) {
        mRecordList.clear();
      }
      Iterable it = value.data!;
      it.map((e) => mRecordList.add(e)).toList();
      setState(() {});
    }).catchError((e) {
      isLastPage = true;
      appStore.setLoading(false);
      setState(() {});
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _animation,
      builder: (context, child) {
        return FractionallySizedBox(
          heightFactor: 0.7 * _animation.value,
          child: Container(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  Color(0xFFFFA726),
                  primaryColor,
                ],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
             // color: primaryColor,
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
            ),
            child: Column(
              children: [
                10.height,
                Container(
                  width: 40,
                  height: 5,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.3),
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  'Leaderboard',
                  style: boldTextStyle(
                    color: Colors.white,
                    size: 20,
                   weight: FontWeight.bold
                  ),
                ),
                20.height,
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: mRecordList.length,
                    itemBuilder: (context, index) {
                      return PlayerListItem(
                        playerRank: index + 1,
                        playerName: mRecordList[index].userName??'',
                        score:  mRecordList[index].score.toString(),
                        countryFlag:  mRecordList[index].flagUrl.toString(),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class PlayerListItem extends StatelessWidget {
  final int playerRank;
  final String playerName;
  final String score;
  final String countryFlag;

  const PlayerListItem({
    Key? key,
    required this.playerRank,
    required this.playerName,
    required this.score,
    required this.countryFlag,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(vertical: 6),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
     /* decoration: BoxDecoration(
        color: primaryLightColor, // Slightly lighter green
        borderRadius: BorderRadius.circular(10),
      ),*/
      decoration: BoxDecoration(
        border: Border.all(color: Colors.white10.withAlpha(80)),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.white.withAlpha(100),
            blurRadius: 10.0,
            spreadRadius: 0.0,
          ),
        ],
        color: Colors.white.withOpacity(0.2),
      ),
      child: Row(
        children: [
          // Rank
          SizedBox(
            width: 30,
            child: Text(
              '$playerRank',
              style: const TextStyle(
                color: Colors.black54,
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          cachedImage(countryFlag, fit: BoxFit.cover,width: 25,height: 25).cornerRadiusWithClipRRect(20),
          12.width,
          Expanded(
            child: Text(
              playerName,
              style: const TextStyle(
                color:Colors.black54,
                fontSize: 16,
              ),
            ),
          ),
          Text(
            score,
            style: const TextStyle(
              color: Colors.black54,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}