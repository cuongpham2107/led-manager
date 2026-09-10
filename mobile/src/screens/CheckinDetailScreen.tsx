import {
  ArrowLeft,
  Box,
  Calendar,
  CheckCircle2,
  Circle,
  Cog,
  MapPin,
  PackageCheck,
  QrCode,
  ShoppingCart,
  Truck,
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
import { CheckinBatch, CheckinBatchItem, CheckinBatchTypeValue } from '../types';

interface CheckinDetailScreenProps {
  batchId: number;
  onBack: () => void;
}

const BatchTypeIcon: React.FC<{ type: CheckinBatchTypeValue; size?: number }> = ({ type, size = 16 }) => {
  if (type === 'production') return <Cog color="#475569" size={size} />;
  if (type === 'purchase') return <ShoppingCart color="#475569" size={size} />;
  return <Truck color="#475569" size={size} />;
};

export const CheckinDetailScreen: React.FC<CheckinDetailScreenProps> = ({
  batchId,
  onBack,
}) => {
  const [batch, setBatch] = useState<CheckinBatch | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isCompleting, setIsCompleting] = useState<boolean>(false);
  const [showScanner, setShowScanner] = useState<boolean>(false);

  const fetchBatchDetail = async () => {
    try {
      const response = await apiClient.get(`/checkin-batches/${batchId}`);
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
    const response = await apiClient.post(`/checkin-batches/${batchId}/scan`, {
      code,
      condition: 'ok',
    });
    if (response.data?.success) {
      await fetchBatchDetail();
    }
  };

  const handleCompleteCheckin = () => {
    Alert.alert(
      'Xác nhận hoàn tất nhập kho',
      `Bạn có chắc chắn muốn hoàn tất đợt nhập ${batch?.code}? Toàn bộ thiết bị đã quét sẽ chuyển sang trạng thái "Sẵn sàng" (Ready).`,
      [
        { text: 'Kiểm tra lại', style: 'cancel' },
        {
          text: 'Xác nhận hoàn tất',
          onPress: async () => {
            setIsCompleting(true);
            try {
              const response = await apiClient.post(`/checkin-batches/${batchId}/complete`);
              if (response.data?.success) {
                Alert.alert('Thành công', response.data.message);
                await fetchBatchDetail();
              }
            } catch (err: any) {
              const msg = err?.response?.data?.message || 'Không thể hoàn tất nhập kho.';
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
          <Text style={styles.loadingText}>Đang tải thông tin đợt nhập kho...</Text>
        </View>
      </SafeAreaView>
    );
  }

  const isCompleted = batch.status.value === 'completed' || batch.status.value === 'cancelled';
  const canComplete = !isCompleted && batch.scanned_count > 0;

  const sortedItems = [...(batch.items || [])].sort(
    (a, b) => Number(a.is_received) - Number(b.is_received)
  );
  const pendingCount = (batch.items || []).filter((i) => !i.is_received).length;

  const renderItem = ({ item, index }: { item: CheckinBatchItem; index: number }) => {
    const isReceived = !!item.is_received;

    return (
      <View style={[styles.itemRow, !isReceived && styles.itemRowPending]}>
        <View style={[styles.itemIndex, !isReceived && styles.itemIndexPending]}>
          <Text style={styles.itemIndexText}>{index + 1}</Text>
        </View>
        <View style={styles.itemInfo}>
          <Text style={styles.itemSerial}>{item.asset?.serial_no || `Thiết bị #${item.asset_id}`}</Text>
          <Text style={styles.itemLine}>
            {item.asset?.product_line?.name || 'Cabinet LED'} {item.asset?.size ? `(${item.asset.size})` : ''}
          </Text>
        </View>
        <View style={styles.itemTimeWrap}>
          {isReceived ? (
            <>
              <CheckCircle2 color="#10B981" size={16} />
              <Text style={styles.itemTime}>Đã nhập</Text>
            </>
          ) : (
            <>
              <Circle color="#94A3B8" size={16} />
              <Text style={styles.itemTimePending}>Chưa quét</Text>
            </>
          )}
        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={onBack} style={styles.backBtn}>
          <ArrowLeft color="#0F172A" size={22} />
        </TouchableOpacity>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerTitle}>{batch.code}</Text>
          <Text style={styles.headerSubtitle}>
            {batch.batch_type.label} — {batch.product_line?.name || 'LED'}
          </Text>
        </View>
        <StatusBadge label={batch.status.label} color={batch.status.color} size="sm" />
      </View>

      <FlatList
        data={sortedItems}
        keyExtractor={(item) => item.id.toString()}
        renderItem={renderItem}
        contentContainerStyle={styles.scrollContent}
        ListHeaderComponent={
          <>
            {/* Batch Info Card */}
            <View style={styles.infoCard}>
              <View style={styles.infoRow}>
                <BatchTypeIcon type={batch.batch_type.value} size={16} />
                <Text style={styles.infoText}>
                  Loại: <Text style={styles.boldText}>{batch.batch_type.label}</Text>
                </Text>
              </View>
              <View style={styles.infoRow}>
                <Box color="#64748B" size={16} />
                <Text style={styles.infoText}>
                  Dòng SP: <Text style={styles.boldText}>{batch.product_line?.name || 'N/A'}</Text>
                </Text>
              </View>
              <View style={styles.infoRow}>
                <MapPin color="#64748B" size={16} />
                <Text style={styles.infoText}>
                  Kho nhập: <Text style={styles.boldText}>{batch.warehouse?.name || 'Kho chính'}</Text>
                </Text>
              </View>
              {batch.expected_date ? (
                <View style={styles.infoRow}>
                  <Calendar color="#64748B" size={16} />
                  <Text style={styles.infoText}>
                    Ngày dự kiến: <Text style={styles.boldText}>{batch.expected_date}</Text>
                  </Text>
                </View>
              ) : null}
              {batch.production_note ? (
                <View style={styles.noteBox}>
                  <Text style={styles.noteLabel}>Ghi chú sản xuất:</Text>
                  <Text style={styles.noteBody}>{batch.production_note}</Text>
                </View>
              ) : null}
            </View>

            {/* Progress Big Section */}
            <View style={styles.progressCard}>
              <View style={styles.progressHeader}>
                <Text style={styles.progressCardTitle}>Tiến Độ Nhập Kho</Text>
                <Text style={styles.progressScore}>
                  {batch.scanned_count} / {batch.target_items_count} thiết bị
                </Text>
              </View>

              <View style={styles.progressBarBg}>
                <View style={[styles.progressBarFill, { width: `${batch.progress_percent}%` }]} />
              </View>

              <Text style={styles.progressHint}>
                Đã nhập {batch.progress_percent}% tổng số lượng dự kiến.
              </Text>
            </View>

            {/* Scan Action Big Button */}
            {!isCompleted && (
              <TouchableOpacity
                style={styles.scanActionBtn}
                onPress={() => setShowScanner(true)}
              >
                <QrCode color="#fff" size={24} style={{ marginRight: 10 }} />
                <Text style={styles.scanActionBtnText}>BẮN MÃ QR NHẬP KHO</Text>
              </TouchableOpacity>
            )}

            <Text style={styles.scannedListTitle}>
              Danh sách thiết bị ({batch.scanned_count} đã nhập{pendingCount > 0 ? ` / ${pendingCount} chưa quét` : ''})
            </Text>
          </>
        }
        ListEmptyComponent={
          <View style={styles.emptyItemsBox}>
            <Text style={styles.emptyItemsText}>Chưa có thiết bị nào được nhập trong đợt này.</Text>
            <Text style={styles.emptyItemsSub}>Bấm nút "BẮN MÃ QR NHẬP KHO" phía trên để bắt đầu.</Text>
          </View>
        }
      />

      {/* Bottom Complete Button */}
      {canComplete && (
        <View style={styles.footerBar}>
          <TouchableOpacity
            style={[styles.completeBtn, isCompleting && styles.completeBtnDisabled]}
            onPress={handleCompleteCheckin}
            disabled={isCompleting}
          >
            {isCompleting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <>
                <PackageCheck color="#fff" size={20} style={{ marginRight: 8 }} />
                <Text style={styles.completeBtnText}>
                  Xác nhận hoàn tất ({batch.scanned_count} thiết bị)
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
        title={`Quét Nhập Kho: ${batch.code}`}
        subtitle={`Đã nhập: ${batch.scanned_count} / ${batch.target_items_count}`}
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
  noteBox: {
    marginTop: 8,
    padding: 10,
    backgroundColor: '#F8FAFC',
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  noteLabel: {
    color: '#64748B',
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 2,
  },
  noteBody: {
    color: '#0F172A',
    fontSize: 13,
  },
  progressCard: {
    backgroundColor: '#ECFDF5',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  progressHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  progressCardTitle: {
    color: '#065F46',
    fontSize: 15,
    fontWeight: '700',
  },
  progressScore: {
    color: '#10B981',
    fontSize: 15,
    fontWeight: '800',
  },
  progressBarBg: {
    height: 10,
    backgroundColor: '#D1FAE5',
    borderRadius: 5,
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    backgroundColor: '#10B981',
    borderRadius: 5,
  },
  progressHint: {
    color: '#059669',
    fontSize: 12,
    marginTop: 8,
  },
  scanActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#10B981',
    borderRadius: 16,
    paddingVertical: 16,
    marginBottom: 20,
    shadowColor: '#10B981',
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
  itemSerialPending: {
    color: '#94A3B8',
    fontSize: 14,
    fontWeight: '700',
    fontStyle: 'italic',
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
  itemTimePending: {
    color: '#94A3B8',
    fontSize: 12,
    fontWeight: '700',
  },
  itemRowPending: {
    backgroundColor: '#F8FAFC',
    borderStyle: 'dashed',
    borderColor: '#CBD5E1',
  },
  itemIndexPending: {
    backgroundColor: '#E2E8F0',
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
  completeBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#059669',
    borderRadius: 14,
    paddingVertical: 14,
  },
  completeBtnDisabled: {
    backgroundColor: '#6EE7B7',
  },
  completeBtnText: {
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
