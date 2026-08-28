import {
  ArrowLeft,
  Calendar,
  CheckCircle2,
  Clock,
  Layers,
  MapPin,
  QrCode,
  Truck,
  User,
} from 'lucide-react-native';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  SafeAreaView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { ScannerModal } from '../components/ScannerModal';
import { StatusBadge } from '../components/StatusBadge';
import { apiClient } from '../services/apiClient';
import { CheckoutBatch, CheckoutBatchItem } from '../types';

interface CheckoutDetailScreenProps {
  batchId: number;
  onBack: () => void;
}

export const CheckoutDetailScreen: React.FC<CheckoutDetailScreenProps> = ({
  batchId,
  onBack,
}) => {
  const [batch, setBatch] = useState<CheckoutBatch | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isCompleting, setIsCompleting] = useState<boolean>(false);
  const [showScanner, setShowScanner] = useState<boolean>(false);

  const fetchBatchDetail = async () => {
    try {
      const response = await apiClient.get(`/checkout-batches/${batchId}`);
      if (response.data?.success) {
        setBatch(response.data.data);
      }
    } catch {
      // Ignore
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchBatchDetail();
  }, [batchId]);

  const handleScanCode = async (code: string) => {
    const response = await apiClient.post(`/checkout-batches/${batchId}/scan`, { code });
    if (response.data?.success) {
      await fetchBatchDetail();
    }
  };

  const handleCompleteDispatch = () => {
    Alert.alert(
      'Xác nhận Xuất kho đi sự kiện',
      `Bạn có chắc chắn muốn hoàn tất xuất kho cho phiếu ${batch?.code}? Toàn bộ thiết bị đã quét sẽ chuyển sang trạng thái "Đang chạy sự kiện".`,
      [
        { text: 'Kiểm tra lại', style: 'cancel' },
        {
          text: 'Xác nhận Xuất kho',
          onPress: async () => {
            setIsCompleting(true);
            try {
              const response = await apiClient.post(`/checkout-batches/${batchId}/complete`);
              if (response.data?.success) {
                Alert.alert('Thành công', response.data.message);
                await fetchBatchDetail();
              }
            } catch (err: any) {
              const msg = err?.response?.data?.message || 'Không thể hoàn tất xuất kho.';
              Alert.alert('Lỗi', msg);
            } finally {
              setIsCompleting(false);
            }
          },
        },
      ]
    );
  };

  if (isLoading || !batch) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.centerBox}>
          <ActivityIndicator color="#2563EB" size="large" />
          <Text style={styles.loadingText}>Đang tải thông tin đợt xuất kho...</Text>
        </View>
      </SafeAreaView>
    );
  }

  const isDispatched = batch.status.value === 'dispatched' || batch.status.value === 'completed';

  const renderScannedItem = ({ item, index }: { item: CheckoutBatchItem; index: number }) => (
    <View style={styles.itemRow}>
      <View style={styles.itemIndex}>
        <Text style={styles.itemIndexText}>{index + 1}</Text>
      </View>
      <View style={styles.itemInfo}>
        <Text style={styles.itemSerial}>{item.asset?.serial_no || `Thiết bị #${item.asset_id}`}</Text>
        <Text style={styles.itemLine}>
          {item.asset?.product_line?.name || 'Cabinet LED'} {item.asset?.size ? `(${item.asset.size}m)` : ''}
        </Text>
      </View>
      <View style={styles.itemTimeWrap}>
        <CheckCircle2 color="#059669" size={16} />
        <Text style={styles.itemTime}>Đã quét</Text>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={onBack} style={styles.backBtn}>
          <ArrowLeft color="#0F172A" size={22} />
        </TouchableOpacity>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerTitle}>{batch.code}</Text>
          <Text style={styles.headerSubtitle}>{batch.order?.event || 'Xuất kho sự kiện'}</Text>
        </View>
        <StatusBadge label={batch.status.label} color={batch.status.color} size="sm" />
      </View>

      <FlatList
        data={batch.items || []}
        keyExtractor={(item) => item.id.toString()}
        renderItem={renderScannedItem}
        contentContainerStyle={styles.scrollContent}
        ListHeaderComponent={
          <>
            {/* Batch Info Card */}
            <View style={styles.infoCard}>
              <View style={styles.infoRow}>
                <User color="#64748B" size={16} />
                <Text style={styles.infoText}>Khách hàng: <Text style={styles.boldText}>{batch.customer?.name || 'N/A'}</Text></Text>
              </View>
              <View style={styles.infoRow}>
                <Layers color="#64748B" size={16} />
                <Text style={styles.infoText}>Diện tích: <Text style={styles.boldText}>{batch.required_area_m2} m²</Text></Text>
              </View>
              <View style={styles.infoRow}>
                <MapPin color="#64748B" size={16} />
                <Text style={styles.infoText}>Kho xuất: <Text style={styles.boldText}>{batch.warehouse?.name || 'Kho chính'}</Text></Text>
              </View>
              {batch.expected_return_date ? (
                <View style={styles.infoRow}>
                  <Calendar color="#64748B" size={16} />
                  <Text style={styles.infoText}>Dự kiến trả: <Text style={styles.boldText}>{batch.expected_return_date}</Text></Text>
                </View>
              ) : null}
            </View>

            {/* Progress Big Section */}
            <View style={styles.progressCard}>
              <View style={styles.progressHeader}>
                <Text style={styles.progressCardTitle}>Tiến Độ Bắn Mã QR</Text>
                <Text style={styles.progressScore}>
                  {batch.scanned_count} / {batch.target_cabinets_count} Cabinets
                </Text>
              </View>

              <View style={styles.progressBarBg}>
                <View style={[styles.progressBarFill, { width: `${batch.progress_percent}%` }]} />
              </View>

              <Text style={styles.progressHint}>
                Đã quét hoàn thành {batch.progress_percent}% số lượng thiết bị yêu cầu.
              </Text>
            </View>

            {/* Scan Action Big Button */}
            {!isDispatched && (
              <TouchableOpacity
                style={styles.scanActionBtn}
                onPress={() => setShowScanner(true)}
              >
                <QrCode color="#fff" size={24} style={{ marginRight: 10 }} />
                <Text style={styles.scanActionBtnText}>BẮN MÃ QR THIẾT BỊ</Text>
              </TouchableOpacity>
            )}

            <Text style={styles.scannedListTitle}>
              Danh sách thiết bị đã quét ({batch.items?.length || 0})
            </Text>
          </>
        }
        ListEmptyComponent={
          <View style={styles.emptyItemsBox}>
            <Text style={styles.emptyItemsText}>Chưa có thiết bị nào được quét vào đợt xuất này.</Text>
            <Text style={styles.emptyItemsSub}>Bấm nút "BẮN MÃ QR THIẾT BỊ" phía trên để bắt đầu quét.</Text>
          </View>
        }
      />

      {/* Bottom Dispatch Confirmation Button */}
      {!isDispatched && batch.scanned_count > 0 && (
        <View style={styles.footerBar}>
          <TouchableOpacity
            style={[styles.dispatchBtn, isCompleting && styles.dispatchBtnDisabled]}
            onPress={handleCompleteDispatch}
            disabled={isCompleting}
          >
            {isCompleting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <>
                <Truck color="#fff" size={20} style={{ marginRight: 8 }} />
                <Text style={styles.dispatchBtnText}>
                  Xác Nhận Xuất Kho Đi Sự Kiện ({batch.scanned_count} Cabinet)
                </Text>
              </>
            )}
          </TouchableOpacity>
        </View>
      )}

      {/* Camera Scanner Modal */}
      <ScannerModal
        visible={showScanner}
        onClose={() => setShowScanner(false)}
        onScan={handleScanCode}
        title={`Quét Xuất Kho: ${batch.code}`}
        subtitle={`Đã quét: ${batch.scanned_count} / ${batch.target_cabinets_count}`}
        continuousModeDefault={true}
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 14,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  backBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  headerTitleWrap: {
    flex: 1,
  },
  headerTitle: {
    color: '#0F172A',
    fontSize: 18,
    fontWeight: '800',
  },
  headerSubtitle: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 2,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  infoCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    gap: 8,
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  infoText: {
    color: '#64748B',
    fontSize: 13,
  },
  boldText: {
    color: '#0F172A',
    fontWeight: '700',
  },
  progressCard: {
    backgroundColor: '#EFF6FF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  progressHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  progressCardTitle: {
    color: '#1E3A8A',
    fontSize: 15,
    fontWeight: '700',
  },
  progressScore: {
    color: '#2563EB',
    fontSize: 15,
    fontWeight: '800',
  },
  progressBarBg: {
    height: 10,
    backgroundColor: '#DBEAFE',
    borderRadius: 5,
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    backgroundColor: '#2563EB',
    borderRadius: 5,
  },
  progressHint: {
    color: '#3B82F6',
    fontSize: 12,
    marginTop: 8,
  },
  scanActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#2563EB',
    borderRadius: 16,
    paddingVertical: 16,
    marginBottom: 20,
    shadowColor: '#2563EB',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 3,
  },
  scanActionBtnText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  scannedListTitle: {
    color: '#334155',
    fontSize: 14,
    fontWeight: '800',
    marginBottom: 10,
    letterSpacing: 0.2,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  itemIndex: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  itemIndexText: {
    color: '#64748B',
    fontSize: 12,
    fontWeight: '700',
  },
  itemInfo: {
    flex: 1,
  },
  itemSerial: {
    color: '#0F172A',
    fontSize: 14,
    fontWeight: '700',
  },
  itemLine: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 2,
  },
  itemTimeWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  itemTime: {
    color: '#059669',
    fontSize: 12,
    fontWeight: '700',
  },
  emptyItemsBox: {
    padding: 24,
    alignItems: 'center',
  },
  emptyItemsText: {
    color: '#64748B',
    fontSize: 14,
    textAlign: 'center',
  },
  emptyItemsSub: {
    color: '#94A3B8',
    fontSize: 12,
    textAlign: 'center',
    marginTop: 4,
  },
  footerBar: {
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
  },
  dispatchBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#059669',
    borderRadius: 14,
    paddingVertical: 14,
  },
  dispatchBtnDisabled: {
    backgroundColor: '#6EE7B7',
  },
  dispatchBtnText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '700',
  },
  centerBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    color: '#64748B',
    fontSize: 14,
    marginTop: 12,
  },
});
