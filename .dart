import 'package:flutter/material.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';
import 'dart:convert';

class PatientTrackingScreen extends StatefulWidget {
  final int patientId; // مثال: 11
  final String userToken; // توكن الطبيب أو فرد العائلة للمصادقة

  const PatientTrackingScreen({Key? key, required this.patientId, required this.userToken}) : super(key: key);

  @override
  _PatientTrackingScreenState createState() => _PatientTrackingScreenState();
}

class _PatientTrackingScreenState extends State<PatientTrackingScreen> {
  PusherChannelsFlutter pusher = PusherChannelsFlutter.getInstance();
  
  // متغيرات لحفظ الإحداثيات الحية
  double currentLocationLat = 0.0;
  double currentLocationLng = 0.0;

  @override
  void initState() {
    super.initState();
    initPusher();
  }

  Future<void> initPusher() async {
    try {
      await pusher.init(
        apiKey: "YOUR_REVERB_APP_KEY", // استبدلها بالـ Key الخاص بك
        cluster: "mt1",
        useTLS: false, // اجعلها true إذا قمت بتركيب SSL (https) على السيرفر
        host: "72.61.180.135", // الـ IP الخاص بسيرفر الإنتاج
        wsPort: 8080, // بورت Reverb
        
        // إعدادات المصادقة (إذا كانت القناة Private)
        authEndpoint: "http://72.61.180.135/api/broadcasting/auth",
        authParams: {
          'headers': {
            'Authorization': 'Bearer ${widget.userToken}',
            'Accept': 'application/json',
          }
        },
        
        // دوال الاستماع للأحداث
        onConnectionStateChange: onConnectionStateChange,
        onError: onError,
        onEvent: onEvent,
      );

      // اسم القناة (يجب أن يتطابق مع ما تبث عليه في لارافيل)
      // إذا كانت عامة: tracking.11
      // إذا كانت خاصة: private-tracking.11
      await pusher.subscribe(channelName: 'private-tracking.${widget.patientId}');
      
      await pusher.connect();
    } catch (e) {
      print("ERROR: $e");
    }
  }

  // دالة استقبال البيانات اللحظية
  void onEvent(PusherEvent event) {
    print("Event Received: ${event.eventName}");
    
    // اسم الحدث الذي يرسله لارافيل (مثلاً LocationUpdated)
    if (event.eventName == 'App\\Events\\LocationUpdated' || event.eventName == 'LocationUpdated') {
      final data = jsonDecode(event.data.toString());
      
      setState(() {
        // استخراج الإحداثيات من الـ Payload القادم من الباك إند
        currentLocationLat = data['lat'];
        currentLocationLng = data['lng'];
        
        // هنا يمكن استدعاء دالة لتحريك الـ Marker الخاص بـ Google Maps
        // updateMapMarker(currentLocationLat, currentLocationLng);
      });
    }
  }

  void onConnectionStateChange(dynamic currentState, dynamic previousState) {
    print("Connection: $currentState");
  }

  void onError(String message, int? code, dynamic e) {
    print("Pusher Error: $message");
  }

  @override
  void dispose() {
    pusher.unsubscribe(channelName: 'private-tracking.${widget.patientId}');
    pusher.disconnect();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تتبع المريض الحي')),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('إحداثيات المريض الحالية:', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            SizedBox(height: 10),
            Text('Latitude: $currentLocationLat', style: TextStyle(fontSize: 16)),
            Text('Longitude: $currentLocationLng', style: TextStyle(fontSize: 16)),
            // هنا يتم وضع الـ GoogleMap Widget
          ],
        ),
      ),
    );
  }
}