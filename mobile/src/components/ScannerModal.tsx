import { CameraView, useCameraPermissions } from 'expo-camera';
import * as Haptics from 'expo-haptics';
import { Camera, Check, Flashlight, RefreshCw, X } from 'lucide-react-native';
import React, { useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Platform,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';

interface ScannerModalProps {
  visible: boolean;
  onClose: () => void;
  onScan: (code: string) => Promise<boolean | void>;
  title?: string;
  subtitle?: string;
  continuousModeDefault?: boolean;
  pendingCodes?: string[];
}

interface WebCameraViewProps {
  onBarcodeScanned: ({ data }: { data: string }) => void;
  isProcessing: boolean;
  onPhotoCapture: () => void;
}

const WebCameraView: React.FC<WebCameraViewProps> = ({
  onBarcodeScanned,
  isProcessing,
  onPhotoCapture,
}) => {
  const videoRef = React.useRef<HTMLVideoElement | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [retryCount, setRetryCount] = useState<number>(0);
  const streamRef = React.useRef<MediaStream | null>(null);

  React.useEffect(() => {
    let active = true;
    let scanTimer: any = null;

    const startWebcam = async () => {
      setLoading(true);
      setErrorMsg(null);

      if (typeof navigator === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
        if (typeof window !== 'undefined' && window.isSecureContext === false) {
          setErrorMsg(
            'Trình duyệt chặn Camera do đang truy cập qua HTTP (không phải HTTPS hoặc localhost).'
          );
        } else {
          setErrorMsg('Trình duyệt không hỗ trợ mở camera (navigator.mediaDevices.getUserMedia).');
        }
        setLoading(false);
        return;
      }

      try {
        let stream: MediaStream;
        try {
          stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
          });
        } catch {
          stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: false,
          });
        }

        if (!active) {
          stream.getTracks().forEach((t) => t.stop());
          return;
        }

        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.play().catch((e) => console.warn('Video play error:', e));
        }
        setLoading(false);

        if (typeof window !== 'undefined' && 'BarcodeDetector' in window) {
          try {
            const detector = new (window as any).BarcodeDetector({
              formats: ['qr_code', 'code_128', 'code_39', 'ean_13'],
            });

            scanTimer = setInterval(async () => {
              if (!videoRef.current || isProcessing || videoRef.current.readyState < 2) return;
              try {
                const barcodes = await detector.detect(videoRef.current);
                if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                  onBarcodeScanned({ data: barcodes[0].rawValue });
                }
              } catch {
                // Ignore detection frame drops
              }
            }, 300);
          } catch (e) {
            console.warn('BarcodeDetector error:', e);
          }
        }
      } catch (err: any) {
        console.error('Webcam start error:', err);
        let msg = 'Không thể bật máy ảnh của máy tính.';
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
          msg = 'Quyền Camera đang bị chặn. Hãy bấm vào biểu tượng 🔒 hoặc 📷 trên thanh địa chỉ URL của trình duyệt và chọn "Cho phép" (Allow Camera), sau đó thử lại.';
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
          msg = 'Không tìm thấy Webcam/Camera nào được kết nối với máy tính.';
        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
          msg = 'Máy ảnh đang được sử dụng bởi ứng dụng khác (Zoom, Meet, Camera app...). Hãy tắt ứng dụng đó rồi thử lại.';
        }
        setErrorMsg(msg);
        setLoading(false);
      }
    };

    startWebcam();

    return () => {
      active = false;
      if (scanTimer) clearInterval(scanTimer);
      if (streamRef.current) {
        streamRef.current.getTracks().forEach((t) => t.stop());
        streamRef.current = null;
      }
    };
  }, [retryCount]);

  if (errorMsg) {
    return (
      <View style={styles.permissionBox}>
        <Camera color="#EF4444" size={56} style={{ marginBottom: 16 }} />
        <Text style={[styles.permissionText, { color: '#FCA5A5' }]}>{errorMsg}</Text>
        <View style={{ flexDirection: 'row', gap: 10, flexWrap: 'wrap', justifyContent: 'center', marginTop: 12 }}>
          <TouchableOpacity
            onPress={() => setRetryCount((c) => c + 1)}
            style={[styles.permissionBtn, { backgroundColor: '#2563EB' }]}
          >
            <Text style={styles.permissionBtnText}>🔄 Thử bật lại Camera</Text>
          </TouchableOpacity>
          <TouchableOpacity
            onPress={onPhotoCapture}
            style={[styles.permissionBtn, { backgroundColor: '#10B981' }]}
          >
            <Text style={styles.permissionBtnText}>📷 Chụp ảnh / Tải ảnh QR</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View style={StyleSheet.absoluteFill}>
      {React.createElement('video', {
        ref: videoRef,
        autoPlay: true,
        playsInline: true,
        muted: true,
        style: {
          width: '100%',
          height: '100%',
          objectFit: 'cover',
          backgroundColor: '#000',
        },
      })}
      {loading && (
        <View style={[StyleSheet.absoluteFill, { justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(0,0,0,0.7)' }]}>
          <ActivityIndicator color="#3B82F6" size="large" />
          <Text style={{ color: '#94A3B8', marginTop: 12, fontSize: 13 }}>Đang khởi động máy ảnh...</Text>
        </View>
      )}
      <View style={styles.overlay}>
        <View style={styles.targetFrame}>
          <View style={[styles.corner, styles.topLeft]} />
          <View style={[styles.corner, styles.topRight]} />
          <View style={[styles.corner, styles.bottomLeft]} />
          <View style={[styles.corner, styles.bottomRight]} />
          <View style={styles.laserLine} />
        </View>
      </View>
    </View>
  );
};

export const ScannerModal: React.FC<ScannerModalProps> = ({
  visible,
  onClose,
  onScan,
  title = 'Quét mã QR / Barcode',
  subtitle = 'Hướng camera về phía mã QR trên thiết bị',
  continuousModeDefault = true,
  pendingCodes = [],
}) => {
  const [permission, requestPermission] = useCameraPermissions();
  const [torch, setTorch] = useState<boolean>(false);
  const [continuous, setContinuous] = useState<boolean>(continuousModeDefault);
  const [isProcessing, setIsProcessing] = useState<boolean>(false);
  const [lastScanned, setLastScanned] = useState<string | null>(null);
  const [manualCode, setManualCode] = useState<string>('');
  const [scanStatus, setScanStatus] = useState<'idle' | 'success' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string>('');

  const triggerHaptic = async (type: 'success' | 'error') => {
    try {
      if (Platform.OS !== 'web') {
        if (type === 'success') {
          await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        } else {
          await Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
        }
      }
    } catch {
      // Ignore
    }
  };

  const handleBarcodeScanned = async ({ data }: { data: string }) => {
    if (isProcessing || !data) return;

    const trimmed = data.trim();
    if (!trimmed) return;

    // Prevent immediate duplicate firing in continuous mode within 2 seconds
    if (lastScanned === trimmed && scanStatus === 'success') {
      return;
    }

    setIsProcessing(true);
    setLastScanned(trimmed);

    try {
      await onScan(trimmed);
      setScanStatus('success');
      setStatusMessage(`Đã quét: ${trimmed}`);
      await triggerHaptic('success');

      if (!continuous) {
        setTimeout(() => {
          setIsProcessing(false);
          onClose();
        }, 600);
      } else {
        setTimeout(() => {
          setIsProcessing(false);
          setScanStatus('idle');
        }, 1500);
      }
    } catch (err: any) {
      setScanStatus('error');
      const msg = err?.response?.data?.message || err?.message || 'Quét mã thất bại';
      setStatusMessage(msg);
      await triggerHaptic('error');

      setTimeout(() => {
        setIsProcessing(false);
        setScanStatus('idle');
      }, 2000);
    }
  };

  const handleManualSubmit = async () => {
    if (!manualCode.trim()) return;
    await handleBarcodeScanned({ data: manualCode.trim() });
    setManualCode('');
  };

  const handlePhotoCapture = () => {
    if (typeof document !== 'undefined') {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.setAttribute('capture', 'environment');
      input.onchange = async (e: any) => {
        const file = e.target?.files?.[0];
        if (!file) return;

        if (typeof window !== 'undefined' && 'BarcodeDetector' in window) {
          try {
            const barcodeDetector = new (window as any).BarcodeDetector({
              formats: ['qr_code', 'code_128', 'code_39', 'ean_13'],
            });
            const bitmap = await createImageBitmap(file);
            const barcodes = await barcodeDetector.detect(bitmap);
            if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
              await handleBarcodeScanned({ data: barcodes[0].rawValue });
              return;
            }
          } catch (err) {
            console.warn('BarcodeDetector error:', err);
          }
        }

        const nameWithoutExt = file.name.replace(/\.[^/.]+$/, '');
        const promptCode = window.prompt(
          'Đã chọn/chụp ảnh. Nhập mã QR hoặc số Serial hiển thị trên ảnh:',
          nameWithoutExt.length > 2 && !nameWithoutExt.startsWith('image') ? nameWithoutExt : ''
        );
        if (promptCode && promptCode.trim()) {
          await handleBarcodeScanned({ data: promptCode.trim() });
        }
      };
      input.click();
    }
  };

  return (
    <Modal visible={visible} animationType="slide" presentationStyle="fullScreen">
      <SafeAreaView style={styles.container}>
        {/* Top Header */}
        <View style={styles.header}>
          <TouchableOpacity onPress={onClose} style={styles.iconBtn}>
            <X color="#fff" size={24} />
          </TouchableOpacity>
          <View style={styles.headerTitleWrap}>
            <Text style={styles.headerTitle}>{title}</Text>
            <Text style={styles.headerSubtitle}>{subtitle}</Text>
          </View>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
            <TouchableOpacity
              onPress={handlePhotoCapture}
              style={[styles.iconBtn, { backgroundColor: '#10B981' }]}
            >
              <Camera color="#fff" size={20} />
            </TouchableOpacity>
            <TouchableOpacity
              onPress={() => setTorch((prev) => !prev)}
              style={[styles.iconBtn, torch && styles.iconBtnActive]}
            >
              <Flashlight color={torch ? '#F59E0B' : '#fff'} size={22} />
            </TouchableOpacity>
          </View>
        </View>

        {/* Camera Viewfinder */}
        <View style={styles.cameraContainer}>
          {Platform.OS === 'web' ? (
            <WebCameraView
              onBarcodeScanned={handleBarcodeScanned}
              isProcessing={isProcessing}
              onPhotoCapture={handlePhotoCapture}
            />
          ) : !permission?.granted ? (
            <View style={styles.permissionBox}>
              <Camera color="#94A3B8" size={56} style={{ marginBottom: 16 }} />
              <Text style={styles.permissionText}>
                Ứng dụng cần quyền Camera để quét mã QR thiết bị
              </Text>
              <View style={{ flexDirection: 'row', gap: 10, flexWrap: 'wrap', justifyContent: 'center' }}>
                <TouchableOpacity onPress={requestPermission} style={styles.permissionBtn}>
                  <Text style={styles.permissionBtnText}>Cấp quyền Camera</Text>
                </TouchableOpacity>
                <TouchableOpacity onPress={handlePhotoCapture} style={[styles.permissionBtn, { backgroundColor: '#10B981' }]}>
                  <Text style={styles.permissionBtnText}>📷 Chụp ảnh / Tải ảnh QR</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : (
            <CameraView
              style={StyleSheet.absoluteFill}
              facing="back"
              enableTorch={torch}
              barcodeScannerSettings={{
                barcodeTypes: ['qr', 'code128', 'code39', 'ean13', 'ean8', 'pdf417'],
              }}
              onBarcodeScanned={isProcessing ? undefined : handleBarcodeScanned}
            >
              {/* Overlay with scanning laser frame */}
              <View style={styles.overlay}>
                <View style={styles.targetFrame}>
                  <View style={[styles.corner, styles.topLeft]} />
                  <View style={[styles.corner, styles.topRight]} />
                  <View style={[styles.corner, styles.bottomLeft]} />
                  <View style={[styles.corner, styles.bottomRight]} />
                  <View style={styles.laserLine} />
                </View>
              </View>
            </CameraView>
          )}

          {/* Real-time Status Floating Banner */}
          {scanStatus !== 'idle' && (
            <View
              style={[
                styles.statusBanner,
                scanStatus === 'success' ? styles.statusSuccess : styles.statusError,
              ]}
            >
              {scanStatus === 'success' ? (
                <Check color="#fff" size={20} style={{ marginRight: 8 }} />
              ) : null}
              <Text style={styles.statusText} numberOfLines={2}>
                {statusMessage}
              </Text>
            </View>
          )}
        </View>

        {/* Bottom Control Bar */}
        <View style={styles.bottomControls}>
          {/* Quick pick pending codes if available */}
          {pendingCodes && pendingCodes.length > 0 && (
            <View style={styles.quickPickContainer}>
              <Text style={styles.quickPickTitle}>Mã trong đợt (chạm để quét nhanh):</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.quickPickScroll}>
                {pendingCodes.slice(0, 25).map((code) => (
                  <TouchableOpacity
                    key={code}
                    style={styles.quickPickChip}
                    onPress={() => handleBarcodeScanned({ data: code })}
                    disabled={isProcessing}
                  >
                    <Text style={styles.quickPickChipText}>{code}</Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>
            </View>
          )}

          {/* Continuous mode toggle and Photo capture row */}
          <View style={styles.modeRow}>
            <TouchableOpacity
              style={[styles.modeToggle, continuous && styles.modeToggleActive]}
              onPress={() => setContinuous((prev) => !prev)}
            >
              <RefreshCw color={continuous ? '#2563EB' : '#94A3B8'} size={18} />
              <Text style={[styles.modeToggleText, continuous && styles.modeToggleTextActive]}>
                Quét liên tục: {continuous ? 'BẬT' : 'TẮT'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.photoActionBtn}
              onPress={handlePhotoCapture}
              disabled={isProcessing}
            >
              <Camera color="#10B981" size={16} />
              <Text style={styles.photoActionBtnText}>Chụp / Chọn ảnh</Text>
            </TouchableOpacity>
          </View>

          {/* Manual Input Fallback */}
          <View style={styles.manualRow}>
            <TextInput
              style={styles.manualInput}
              placeholder="Hoặc nhập mã Serial thủ công..."
              placeholderTextColor="#94A3B8"
              value={manualCode}
              onChangeText={setManualCode}
              autoCapitalize="characters"
              returnKeyType="done"
              onSubmitEditing={handleManualSubmit}
            />
            <TouchableOpacity
              style={[styles.manualBtn, !manualCode.trim() && styles.manualBtnDisabled]}
              onPress={handleManualSubmit}
              disabled={!manualCode.trim() || isProcessing}
            >
              {isProcessing ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.manualBtnText}>Nhập</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </SafeAreaView>
    </Modal>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  headerTitleWrap: {
    flex: 1,
    alignItems: 'center',
    paddingHorizontal: 8,
  },
  headerTitle: {
    color: '#F8FAFC',
    fontSize: 17,
    fontWeight: '700',
  },
  headerSubtitle: {
    color: '#94A3B8',
    fontSize: 12,
    marginTop: 2,
  },
  iconBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#1E293B',
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconBtnActive: {
    backgroundColor: '#3B82F6',
  },
  cameraContainer: {
    flex: 1,
    position: 'relative',
    backgroundColor: '#000',
  },
  overlay: {
    ...StyleSheet.absoluteFill,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(0,0,0,0.45)',
  },
  targetFrame: {
    width: 270,
    height: 270,
    position: 'relative',
    backgroundColor: 'transparent',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  laserLine: {
    width: '90%',
    height: 2,
    backgroundColor: '#38BDF8',
    shadowColor: '#38BDF8',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.9,
    shadowRadius: 8,
  },
  corner: {
    position: 'absolute',
    width: 28,
    height: 28,
    borderColor: '#38BDF8',
  },
  topLeft: {
    top: -2,
    left: -2,
    borderTopWidth: 4,
    borderLeftWidth: 4,
    borderTopLeftRadius: 16,
  },
  topRight: {
    top: -2,
    right: -2,
    borderTopWidth: 4,
    borderRightWidth: 4,
    borderTopRightRadius: 16,
  },
  bottomLeft: {
    bottom: -2,
    left: -2,
    borderBottomWidth: 4,
    borderLeftWidth: 4,
    borderBottomLeftRadius: 16,
  },
  bottomRight: {
    bottom: -2,
    right: -2,
    borderBottomWidth: 4,
    borderRightWidth: 4,
    borderBottomRightRadius: 16,
  },
  permissionBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 32,
  },
  permissionText: {
    color: '#E2E8F0',
    fontSize: 16,
    textAlign: 'center',
    marginBottom: 20,
    lineHeight: 22,
  },
  permissionBtn: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 10,
  },
  permissionBtnText: {
    color: '#fff',
    fontSize: 15,
    fontWeight: '700',
  },
  statusBanner: {
    position: 'absolute',
    top: 20,
    left: 20,
    right: 20,
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 8,
  },
  statusSuccess: {
    backgroundColor: '#16A34A',
  },
  statusError: {
    backgroundColor: '#DC2626',
  },
  statusText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '700',
    flex: 1,
  },
  bottomControls: {
    backgroundColor: '#0F172A',
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: '#1E293B',
  },
  modeRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginBottom: 12,
  },
  modeToggle: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#1E293B',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#334155',
  },
  modeToggleActive: {
    backgroundColor: '#EFF6FF',
    borderColor: '#93C5FD',
  },
  modeToggleText: {
    color: '#94A3B8',
    fontSize: 13,
    fontWeight: '600',
    marginLeft: 8,
  },
  modeToggleTextActive: {
    color: '#1D4ED8',
  },
  manualRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  manualInput: {
    flex: 1,
    height: 48,
    backgroundColor: '#1E293B',
    borderRadius: 10,
    paddingHorizontal: 14,
    color: '#F8FAFC',
    fontSize: 14,
    borderWidth: 1,
    borderColor: '#334155',
  },
  manualBtn: {
    height: 48,
    backgroundColor: '#2563EB',
    borderRadius: 10,
    paddingHorizontal: 20,
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 10,
  },
  manualBtnDisabled: {
    backgroundColor: '#334155',
  },
  manualBtnText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '700',
  },
  photoActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#064E3B',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#059669',
    marginLeft: 10,
  },
  photoActionBtnText: {
    color: '#34D399',
    fontSize: 13,
    fontWeight: '600',
    marginLeft: 6,
  },
  quickPickContainer: {
    marginBottom: 10,
  },
  quickPickTitle: {
    color: '#94A3B8',
    fontSize: 11,
    fontWeight: '600',
    marginBottom: 6,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  quickPickScroll: {
    flexDirection: 'row',
  },
  quickPickChip: {
    backgroundColor: '#1E293B',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    marginRight: 8,
    borderWidth: 1,
    borderColor: '#334155',
  },
  quickPickChipText: {
    color: '#38BDF8',
    fontSize: 12,
    fontWeight: '700',
  },
});
