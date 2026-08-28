import { CameraView, useCameraPermissions } from 'expo-camera';
import * as Haptics from 'expo-haptics';
import { Camera, Check, Flashlight, RefreshCw, X } from 'lucide-react-native';
import React, { useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Platform,
  SafeAreaView,
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
}

export const ScannerModal: React.FC<ScannerModalProps> = ({
  visible,
  onClose,
  onScan,
  title = 'Quét mã QR / Barcode',
  subtitle = 'Hướng camera về phía mã QR trên thiết bị',
  continuousModeDefault = true,
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
          <TouchableOpacity
            onPress={() => setTorch((prev) => !prev)}
            style={[styles.iconBtn, torch && styles.iconBtnActive]}
          >
            <Flashlight color={torch ? '#F59E0B' : '#fff'} size={22} />
          </TouchableOpacity>
        </View>

        {/* Camera Viewfinder */}
        <View style={styles.cameraContainer}>
          {!permission?.granted ? (
            <View style={styles.permissionBox}>
              <Camera color="#94A3B8" size={56} style={{ marginBottom: 16 }} />
              <Text style={styles.permissionText}>Ứng dụng cần quyền Camera để quét mã QR thiết bị</Text>
              <TouchableOpacity onPress={requestPermission} style={styles.permissionBtn}>
                <Text style={styles.permissionBtnText}>Cấp quyền Camera</Text>
              </TouchableOpacity>
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
          {/* Continuous mode toggle */}
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
});
