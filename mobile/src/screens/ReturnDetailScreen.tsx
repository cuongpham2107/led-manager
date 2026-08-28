import {
  AlertTriangle,
  ArrowLeft,
  Calendar,
  Check,
  CheckCircle2,
  MapPin,
  QrCode,
  User,
  Wrench,
  X,
} from 'lucide-react-native';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Modal,
  SafeAreaView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { ScannerModal } from '../components/ScannerModal';
import { StatusBadge } from '../components/StatusBadge';
import { apiClient } from '../services/apiClient';
import { ReturnBatch, ReturnBatchItem, ReturnGradeValue } from '../types';

interface ReturnDetailScreenProps {
  batchId: number;
  onBack: () => void;
}

export const ReturnDetailScreen: React.FC<ReturnDetailScreenProps> = ({
  batchId,
  onBack,
}) => {
  const [batch, setBatch] = useState<ReturnBatch | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isCompleting, setIsCompleting] = useState<boolean>(false);
  const [showScanner, setShowScanner] = useState<boolean>(false);

  // Grading Modal State
  const [pendingScanCode, setPendingScanCode] = useState<string | null>(null);
  const [selectedGrade, setSelectedGrade] = useState<ReturnGradeValue>('normal');
  const [gradeNote, setGradeNote] = useState<string>('');
  const [isSubmittingGrade, setIsSubmittingGrade] = useState<boolean>(false);

  const fetchBatchDetail = async () => {
    try {
      const response = await apiClient.get(`/return-batches/${batchId}`);
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

  // When QR is scanned, open Grading Sheet for inspection
  const handleScanCode = async (code: string) => {
    setShowScanner(false);
    setPendingScanCode(code);
    setSelectedGrade('normal');
    setGradeNote('');
  };

  const handleConfirmGrading = async () => {
    if (!pendingScanCode) return;

    setIsSubmittingGrade(true);
    try {
      const response = await apiClient.post(`/return-batches/${batchId}/scan`, {
        code: pendingScanCode,
        grade: selectedGrade,
        grade_note: gradeNote.trim() || undefined,
      });

      if (response.data?.success) {
        setPendingScanCode(null);
        await fetchBatchDetail();
        Alert.alert(
          'Đã tiếp nhận',
          response.data.message,
          [
            { text: 'Xong' },
            {
              text: 'Quét tiếp',
              onPress: () => setShowScanner(true),
            },
          ]
        );
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message || 'Không thể lưu kiểm đếm thiết bị.';
      Alert.alert('Lỗi', msg);
    } finally {
      setIsSubmittingGrade(false);
    }
  };

  const handleCompleteReturn = () => {
    Alert.alert(
      'Hoàn tất Đợt Thu Hồi',
      `Xác nhận đợt thu hồi ${batch?.code} đã kiểm đếm đầy đủ? Thiết bị đạt chuẩn sẽ sẵn sàng trong kho, thiết bị hỏng sẽ được đưa vào danh sách bảo dưỡng.`,
      [
        { text: 'Kiểm tra thêm', style: 'cancel' },
        {
          text: 'Xác nhận Hoàn tất',
          onPress: async () => {
            setIsCompleting(true);
            try {
              const response = await apiClient.post(`/return-batches/${batchId}/complete`);
              if (response.data?.success) {
                Alert.alert('Thành công', response.data.message);
                await fetchBatchDetail();
              }
            } catch (err: any) {
              const msg = err?.response?.data?.message || 'Không thể hoàn tất đợt trả kho.';
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
          <ActivityIndicator color="#059669" size="large" />
          <Text style={styles.loadingText}>Đang tải thông tin đợt thu hồi...</Text>
        </View>
      </SafeAreaView>
    );
  }

  const isCompleted = batch.status.value === 'completed';

  const renderScannedItem = ({ item, index }: { item: ReturnBatchItem; index: number }) => (
    <View style={styles.itemRow}>
      <View style={styles.itemIndex}>
        <Text style={styles.itemIndexText}>{index + 1}</Text>
      </View>
      <View style={styles.itemInfo}>
        <Text style={styles.itemSerial}>{item.asset?.serial_no || `Thiết bị #${item.asset_id}`}</Text>
        <Text style={styles.itemLine}>
          {item.asset?.product_line?.name || 'Cabinet LED'}
        </Text>
        {item.grade_note ? (
          <Text style={styles.itemGradeNote}>Lỗi: {item.grade_note}</Text>
        ) : null}
      </View>
      <View style={styles.gradeBadgeWrap}>
        <StatusBadge label={item.grade.label} color={item.grade.color} size="sm" />
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
          <Text style={styles.headerSubtitle}>{batch.checkout_batch?.order?.event || 'Thu hồi trả kho'}</Text>
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
            {/* Info Card */}
            <View style={styles.infoCard}>
              <View style={styles.infoRow}>
                <User color="#64748B" size={16} />
                <Text style={styles.infoText}>Khách hàng: <Text style={styles.boldText}>{batch.checkout_batch?.customer?.name || 'N/A'}</Text></Text>
              </View>
              <View style={styles.infoRow}>
                <MapPin color="#64748B" size={16} />
                <Text style={styles.infoText}>Kho nhận: <Text style={styles.boldText}>{batch.checkout_batch?.warehouse?.name || 'Kho chính'}</Text></Text>
              </View>
              {batch.return_date ? (
                <View style={styles.infoRow}>
                  <Calendar color="#64748B" size={16} />
                  <Text style={styles.infoText}>Ngày trả: <Text style={styles.boldText}>{batch.return_date}</Text></Text>
                </View>
              ) : null}
            </View>

            {/* Grading Score Tiles */}
            <View style={styles.gradingSummaryCard}>
              <Text style={styles.gradingTitle}>Thống Kê Kiểm Đếm (Grading)</Text>
              <View style={styles.gradingRow}>
                <View style={[styles.gradingBox, { backgroundColor: '#ECFDF5', borderColor: '#A7F3D0' }]}>
                  <Text style={[styles.gradingCount, { color: '#059669' }]}>{batch.normal_count}</Text>
                  <Text style={[styles.gradingLabel, { color: '#047857' }]}>Bình Thường (Đạt)</Text>
                </View>
                <View style={[styles.gradingBox, { backgroundColor: '#FEF2F2', borderColor: '#FECACA' }]}>
                  <Text style={[styles.gradingCount, { color: '#DC2626' }]}>{batch.damaged_count}</Text>
                  <Text style={[styles.gradingLabel, { color: '#B91C1C' }]}>Hỏng Hóc (Sửa)</Text>
                </View>
              </View>
            </View>

            {/* Big Action Scan Button */}
            {!isCompleted && (
              <TouchableOpacity
                style={styles.scanActionBtn}
                onPress={() => setShowScanner(true)}
              >
                <QrCode color="#fff" size={24} style={{ marginRight: 10 }} />
                <Text style={styles.scanActionBtnText}>BẮN MÃ THU HỒI & CHẤM ĐIỂM</Text>
              </TouchableOpacity>
            )}

            <Text style={styles.scannedListTitle}>
              Danh sách thiết bị đã kiểm đếm ({batch.items?.length || 0})
            </Text>
          </>
        }
        ListEmptyComponent={
          <View style={styles.emptyItemsBox}>
            <Text style={styles.emptyItemsText}>Chưa có thiết bị nào được quét thu hồi.</Text>
            <Text style={styles.emptyItemsSub}>Bấm nút "BẮN MÃ THU HỒI" để quét và chấm điểm.</Text>
          </View>
        }
      />

      {/* Complete Return Action */}
      {!isCompleted && batch.total_items_count > 0 && (
        <View style={styles.footerBar}>
          <TouchableOpacity
            style={[styles.completeBtn, isCompleting && styles.completeBtnDisabled]}
            onPress={handleCompleteReturn}
            disabled={isCompleting}
          >
            {isCompleting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <>
                <CheckCircle2 color="#fff" size={20} style={{ marginRight: 8 }} />
                <Text style={styles.completeBtnText}>
                  Xác Nhận Hoàn Tất Nhập Trả Kho ({batch.total_items_count} Thiết bị)
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
        title={`Quét Thu Hồi: ${batch.code}`}
        subtitle="Bắn mã QR để phân loại chất lượng"
        continuousModeDefault={false}
      />

      {/* Quality Grading Bottom Sheet Modal */}
      <Modal visible={!!pendingScanCode} transparent animationType="fade">
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Chấm điểm & Nghiệm thu</Text>
              <TouchableOpacity onPress={() => setPendingScanCode(null)}>
                <X color="#64748B" size={22} />
              </TouchableOpacity>
            </View>

            <Text style={styles.modalCodeText}>Thiết bị: <Text style={styles.modalBold}>{pendingScanCode}</Text></Text>
            <Text style={styles.modalSubText}>Đánh giá tình trạng hoạt động thực tế của Cabinet:</Text>

            {/* Grading Options */}
            <View style={styles.gradeOptionsRow}>
              <TouchableOpacity
                style={[
                  styles.gradeOptionBtn,
                  selectedGrade === 'normal' && styles.gradeOptionBtnNormal,
                ]}
                onPress={() => setSelectedGrade('normal')}
              >
                <CheckCircle2 color={selectedGrade === 'normal' ? '#059669' : '#94A3B8'} size={24} />
                <Text style={[styles.gradeOptionText, selectedGrade === 'normal' && styles.gradeOptionTextActive]}>
                  Bình Thường (Đạt chuẩn)
                </Text>
                <Text style={styles.gradeOptionSub}>Không hỏng hóc, sẵn sàng dùng cho sự kiện tiếp</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.gradeOptionBtn,
                  selectedGrade === 'damaged' && styles.gradeOptionBtnDamaged,
                ]}
                onPress={() => setSelectedGrade('damaged')}
              >
                <AlertTriangle color={selectedGrade === 'damaged' ? '#DC2626' : '#94A3B8'} size={24} />
                <Text style={[styles.gradeOptionText, selectedGrade === 'damaged' && styles.gradeOptionTextDanger]}>
                  Hỏng Hóc / Lỗi Thiết Bị
                </Text>
                <Text style={styles.gradeOptionSub}>Chết bóng LED, hỏng nguồn, cần kỹ thuật bảo dưỡng</Text>
              </TouchableOpacity>
            </View>

            {/* Defect note input if damaged */}
            {selectedGrade === 'damaged' && (
              <View style={styles.defectInputWrap}>
                <Text style={styles.defectLabel}>Mô tả hỏng hóc (để kỹ thuật bảo dưỡng):</Text>
                <TextInput
                  style={styles.defectInput}
                  placeholder="VD: Chết 4 bóng LED góc dưới, gãy chốt cài..."
                  placeholderTextColor="#94A3B8"
                  value={gradeNote}
                  onChangeText={setGradeNote}
                  multiline
                  numberOfLines={2}
                />
              </View>
            )}

            {/* Submit Inspection */}
            <TouchableOpacity
              style={[styles.submitGradeBtn, isSubmittingGrade && styles.submitGradeBtnDisabled]}
              onPress={handleConfirmGrading}
              disabled={isSubmittingGrade}
            >
              {isSubmittingGrade ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.submitGradeBtnText}>Xác Nhận Nhập Kho</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
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
  gradingSummaryCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
  },
  gradingTitle: {
    color: '#0F172A',
    fontSize: 15,
    fontWeight: '700',
    marginBottom: 12,
  },
  gradingRow: {
    flexDirection: 'row',
    gap: 12,
  },
  gradingBox: {
    flex: 1,
    borderRadius: 12,
    padding: 14,
    alignItems: 'center',
    borderWidth: 1,
  },
  gradingCount: {
    fontSize: 24,
    fontWeight: '800',
  },
  gradingLabel: {
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4,
  },
  scanActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#059669',
    borderRadius: 16,
    paddingVertical: 16,
    marginBottom: 20,
    shadowColor: '#059669',
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
  itemGradeNote: {
    color: '#DC2626',
    fontSize: 12,
    marginTop: 4,
    fontWeight: '600',
  },
  gradeBadgeWrap: {
    marginLeft: 8,
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
    backgroundColor: '#2563EB',
    borderRadius: 14,
    paddingVertical: 14,
  },
  completeBtnDisabled: {
    backgroundColor: '#93C5FD',
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
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.6)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#0F172A',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  modalTitle: {
    color: '#0F172A',
    fontSize: 18,
    fontWeight: '800',
  },
  modalCodeText: {
    color: '#64748B',
    fontSize: 14,
    marginBottom: 4,
  },
  modalBold: {
    color: '#2563EB',
    fontWeight: '800',
  },
  modalSubText: {
    color: '#64748B',
    fontSize: 13,
    marginBottom: 16,
  },
  gradeOptionsRow: {
    gap: 12,
    marginBottom: 16,
  },
  gradeOptionBtn: {
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  gradeOptionBtnNormal: {
    backgroundColor: '#ECFDF5',
    borderColor: '#10B981',
  },
  gradeOptionBtnDamaged: {
    backgroundColor: '#FEF2F2',
    borderColor: '#EF4444',
  },
  gradeOptionText: {
    color: '#334155',
    fontSize: 15,
    fontWeight: '700',
    marginTop: 6,
  },
  gradeOptionTextActive: {
    color: '#047857',
  },
  gradeOptionTextDanger: {
    color: '#B91C1C',
  },
  gradeOptionSub: {
    color: '#64748B',
    fontSize: 12,
    marginTop: 2,
  },
  defectInputWrap: {
    marginBottom: 16,
  },
  defectLabel: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '600',
    marginBottom: 6,
  },
  defectInput: {
    backgroundColor: '#FFF',
    borderRadius: 10,
    padding: 12,
    color: '#0F172A',
    fontSize: 13,
    borderWidth: 1,
    borderColor: '#FCA5A5',
  },
  submitGradeBtn: {
    backgroundColor: '#2563EB',
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  submitGradeBtnDisabled: {
    backgroundColor: '#93C5FD',
  },
  submitGradeBtnText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '700',
  },
});
