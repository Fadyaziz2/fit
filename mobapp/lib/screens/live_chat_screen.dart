import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:intl/intl.dart';
import 'package:mighty_fitness/extensions/common.dart';
import 'package:mighty_fitness/extensions/extension_util/context_extensions.dart';
import 'package:mighty_fitness/extensions/extension_util/string_extensions.dart';
import 'package:mighty_fitness/extensions/loader_widget.dart';
import 'package:mighty_fitness/extensions/shared_pref.dart';
import 'package:mighty_fitness/extensions/text_styles.dart';
import 'package:mighty_fitness/main.dart';
import 'package:mighty_fitness/models/live_chat_models.dart';
import 'package:mighty_fitness/network/rest_api.dart';
import 'package:mighty_fitness/utils/app_colors.dart';
import 'package:mighty_fitness/utils/app_config.dart';
import 'package:mighty_fitness/utils/app_constants.dart';
import 'package:mighty_fitness/utils/app_common.dart';
import 'package:mighty_fitness/extensions/widgets.dart';
import 'package:mighty_fitness/extensions/constants.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

class LiveChatScreen extends StatefulWidget {
  const LiveChatScreen({super.key});

  @override
  State<LiveChatScreen> createState() => _LiveChatScreenState();
}

class _LiveChatScreenState extends State<LiveChatScreen> {
  LiveChatThread? _thread;
  List<LiveChatMessage> _messages = [];
  bool _isLoading = true;
  bool _isSending = false;
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  PusherChannelsFlutter? _pusher;
  final DateFormat _dateFormat = DateFormat('MMM d, yyyy h:mm a');

  @override
  void initState() {
    super.initState();
    _loadThread();
  }

  Future<void> _loadThread() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final thread = await fetchLiveChatThread(limit: 100);
      _messages = List<LiveChatMessage>.from(thread.messages)
        ..sort((a, b) => a.createdAt.compareTo(b.createdAt));
      setState(() {
        _thread = thread;
        _isLoading = false;
      });
      _scrollToBottom();
      await _initPusher();
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      toast(e.toString());
    }
  }

  Future<void> _initPusher() async {
    if (_thread == null || _pusher != null) return;
    if (!getBoolAsync(PUSHER_ENABLED)) return;

    final key = getStringAsync(PUSHER_KEY);
    if (key.isEmpty) return;

    final cluster = getStringAsync(PUSHER_CLUSTER).validate(value: 'mt1');

    final instance = PusherChannelsFlutter.getInstance();

    await instance.init(
      apiKey: key,
      cluster: cluster,
      useTLS: true,
      onAuthorizer: (channelName, socketId, options) async {
        final authUrl = Uri.parse('$mBackendURL/broadcasting/auth');
        final response = await http.post(
          authUrl,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': 'Bearer ${getStringAsync(TOKEN)}',
          },
          body: jsonEncode({
            'channel_name': channelName,
            'socket_id': socketId,
          }),
        );

        if (response.statusCode >= 200 && response.statusCode < 300) {
          return response.body;
        }
        throw Exception('Unable to authenticate with chat server');
      },
      onEvent: (event) {
        if (event.eventName != 'ChatMessageSent' || event.data == null) return;
        try {
          final payload = jsonDecode(event.data!);
          final incoming = LiveChatMessage.fromJson(Map<String, dynamic>.from(payload));
          if (_messages.any((element) => element.id == incoming.id)) return;
          _messages.add(incoming);
          _messages.sort((a, b) => a.createdAt.compareTo(b.createdAt));
          if (mounted) {
            setState(() {});
            _scrollToBottom();
          }
        } catch (e) {
          debugPrint(e.toString());
        }
      },
    );

    await instance.subscribe(channelName: 'private-chat.thread.${_thread!.id}');
    await instance.connect();

    _pusher = instance;
  }

  void _scrollToBottom() {
    if (!_scrollController.hasClients) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) return;
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    });
  }

  Future<void> _sendMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty || _isSending) return;

    setState(() {
      _isSending = true;
    });

    try {
      final message = await sendLiveChatMessage(message: text);
      _messageController.clear();
      setState(() {
        _messages.add(message);
        _messages.sort((a, b) => a.createdAt.compareTo(b.createdAt));
        _isSending = false;
      });
      _scrollToBottom();
    } catch (e) {
      setState(() {
        _isSending = false;
      });
      toast(e.toString());
    }
  }

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    _pusher?.disconnect();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: BackgroundColorImageColor,
      appBar: appBarWidget(
        'Live Chat',
        context: context,
        color: BackgroundColorImageColor,
        elevation: 1,
      ),
      body: _isLoading
          ? Center(child: Loader())
          : Column(
              children: [
                Expanded(child: _buildMessages()),
                _buildComposer(),
              ],
            ),
    );
  }

  Widget _buildMessages() {
    if (_thread == null) {
      return Center(
        child: Text('Unable to load messages.', style: boldTextStyle()),
      );
    }

    if (_messages.isEmpty) {
      return Center(
        child: Text(
          'Start the conversation by sending a message.',
          style: secondaryTextStyle(),
          textAlign: TextAlign.center,
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadThread,
      child: ListView.builder(
        controller: _scrollController,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
        itemCount: _messages.length,
        itemBuilder: (context, index) {
          final message = _messages[index];
          final isMe = message.senderType == 'user';
          return Align(
            alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
            child: Container(
              constraints: BoxConstraints(maxWidth: context.width() * 0.75),
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: isMe ? primaryColor : Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment:
                    isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
                children: [
                  Text(
                    message.sender?.name ??
                        (isMe ? userStore.displayName : 'Support'),
                    style: secondaryTextStyle(size: 11,
                        color: isMe ? Colors.white70 : textSecondaryColorGlobal),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    message.message,
                    style: primaryTextStyle(
                      color: isMe ? Colors.white : textPrimaryColorGlobal,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _dateFormat.format(message.createdAt.toLocal()),
                    style: secondaryTextStyle(size: 10,
                        color: isMe ? Colors.white70 : textSecondaryColorGlobal),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildComposer() {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _messageController,
                minLines: 1,
                maxLines: 4,
                textCapitalization: TextCapitalization.sentences,
                decoration: InputDecoration(
                  hintText: 'Type your message...',
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                ),
              ),
            ),
            const SizedBox(width: 12),
            ElevatedButton(
              onPressed: _isSending ? null : _sendMessage,
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                shape: const CircleBorder(),
                padding: const EdgeInsets.all(14),
              ),
              child: _isSending
                  ? const SizedBox(
                      height: 18,
                      width: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    )
                  : const Icon(Icons.send, color: Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}
