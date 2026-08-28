import {
  ArrowLeft,
  Calendar,
  History,
  Layers,
  MapPin,
  QrCode,
  Search,
  Wrench,
} from 'lucide-react-native';
import React, { useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { ScannerModal } from '../components/ScannerModal';
import { StatusBadge } from '../components/StatusBadge';
import { apiClient } from '../services/apiClient';
import { Asset } from '../types';

interface AssetLookupScreenProps {
  onBack: () => void;
}

export const AssetLookupScreen: React.FC<AssetLookupScreenProps> = ({ onBack }) => {
  const [searchCode, setSearchCode] = useState<string>('');
  const [assetData, setAssetData] = useState<{
    asset: Asset;
    recent_status_logs: any[];
    recent_repairs: any[];
  } | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [showScanner, setShowScanner] = useState<boolean>(false);

  const handleLookup = async (code: string) => {
    if (!code.trim()) return;

    setIsLoading(true);
    try {
      const response = await apiClient.get('/assets/lookup', {
        params: { code: code.trim() },
      });

      if (response.data?.success) {
        setAssetData(response.data.data);
        setSearchCode(code.trim());
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message || 'Không tìm thấy thiết bị.';
      Alert.alert('Tra cứu thiết bị', msg);
    } finally {
      setIsLoading(false);
    }
  };

  const handleScanCode = async (code: string) => {
    await handleLookup(code);
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={onBack} style={styles.backBtn}>
          <ArrowLeft color="#0F172A" size={22} />
        </TouchableOpacity>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerTitle}>Tra Cứu Thông Tin Thiết Bị</Text>
          <Text style={styles.headerSubtitle}>Quét mã QR hoặc nhập Serial</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Search & Camera trigger row */}
        <View style={styles.searchSection}>
          <View style={styles.inputWrap}>
            <Search color="#94A3B8" size={18} style={{ marginRight: 8 }} />
            <TextInput
              style={styles.input}
              placeholder="Nhập mã Serial hoặc Barcode..."
              placeholderTextColor="#94A3B8"
              value={searchCode}
              onChangeText={setSearchCode}
              autoCapitalize="characters"
              returnKeyType="search"
              onSubmitEditing={() => handleLookup(searchCode)}
            />
          </View>
          <TouchableOpacity
            style={styles.qrScanBtn}
            onPress={() => setShowScanner(true)}
          >
            <QrCode color="#fff" size={22} />
          </TouchableOpacity>
        </View>

        {isLoading ? (
          <View style={styles.centerBox}>
            <ActivityIndicator color="#7C3AED" size="large" />
            <Text style={styles.loadingText}>Đang tra cứu cơ sở dữ liệu thiết bị...</Text>
          </View>
        ) : assetData ? (
          <View style={styles.resultContainer}>
            {/* Main Asset Spec Card */}
            <View style={styles.specCard}>
              <View style={styles.specHeader}>
                <View>
                  <Text style={styles.specSerial}>{assetData.asset.serial_no}</Text>
                  <Text style={styles.specLine}>
                    {assetData.asset.product_line?.name || 'Màn hình LED'}
                  </Text>
                </View>
                <StatusBadge
                  label={assetData.asset.current_status.label}
                  color={assetData.asset.current_status.color}
                />
              </View>

              <View style={styles.divider} />

              <View style={styles.grid}>
                <View style={styles.gridItem}>
                  <Text style={styles.gridLabel}>Kích thước:</Text>
                  <Text style={styles.gridVal}>{assetData.asset.size || '0.5×0.5 m'}</Text>
                </View>
                <View style={styles.gridItem}>
                  <Text style={styles.gridLabel}>Pixel Pitch:</Text>
                  <Text style={styles.gridVal}>{assetData.asset.product_line?.pitch || '2.6'} mm</Text>
                </View>
                <View style={styles.gridItem}>
                  <Text style={styles.gridLabel}>Kho hiện tại:</Text>
                  <Text style={styles.gridVal}>
                    {assetData.asset.current_warehouse?.name || 'Kho chính'}
                  </Text>
                </View>
                <View style={styles.gridItem}>
                  <Text style={styles.gridLabel}>Mã QR thiết bị:</Text>
                  <Text style={styles.gridVal}>{assetData.asset.qr_code || assetData.asset.serial_no}</Text>
                </View>
              </View>
            </View>

            {/* Status History Logs */}
            <View style={styles.historyCard}>
              <View style={styles.historyHeader}>
                <History color="#2563EB" size={18} />
                <Text style={styles.historyTitle}>Lịch Sử Di Chuyển & Luân Chuyển</Text>
              </View>
              {assetData.recent_status_logs.length === 0 ? (
                <Text style={styles.emptyText}>Chưa có lịch sử trạng thái ghi nhận.</Text>
              ) : (
                assetData.recent_status_logs.map((log: any, idx: number) => (
                  <View key={idx} style={styles.logRow}>
                    <View style={styles.logDot} />
                    <View style={styles.logContent}>
                      <Text style={styles.logNote}>{log.note || `Chuyển sang ${log.to_status}`}</Text>
                      <Text style={styles.logMeta}>
                        Bởi: {log.changed_by || 'Hệ thống'} • {log.created_at ? new Date(log.created_at).toLocaleString('vi-VN') : ''}
                      </Text>
                    </View>
                  </View>
                ))
              )}
            </View>

            {/* Repair & Maintenance History */}
            {assetData.recent_repairs.length > 0 && (
              <View style={styles.repairCard}>
                <View style={styles.historyHeader}>
                  <Wrench color="#DC2626" size={18} />
                  <Text style={[styles.historyTitle, { color: '#B91C1C' }]}>
                    Lịch Sử Sửa Chữa & Bảo Dưỡng
                  </Text>
                </View>
                {assetData.recent_repairs.map((rep: any, idx: number) => (
                  <View key={idx} style={styles.logRow}>
                    <View style={[styles.logDot, { backgroundColor: '#EF4444' }]} />
                    <View style={styles.logContent}>
                      <Text style={styles.logNote}>{rep.repair_note}</Text>
                      <Text style={styles.logMeta}>
                        Trạng thái: {rep.result_status} • Ngày: {rep.start_date}
                      </Text>
                    </View>
                  </View>
                ))}
              </View>
            )}
          </View>
        ) : (
          <View style={styles.placeholderBox}>
            <QrCode color="#CBD5E1" size={80} />
            <Text style={styles.placeholderTitle}>Sẵn sàng tra cứu</Text>
            <Text style={styles.placeholderText}>
              Bấm biểu tượng camera bên trên để quét mã QR hoặc nhập mã Serial vào ô tìm kiếm.
            </Text>
          </View>
        )}
      </ScrollView>

      {/* Scanner Modal */}
      <ScannerModal
        visible={showScanner}
        onClose={() => setShowScanner(false)}
        onScan={handleScanCode}
        title="Quét Mã Tra Cứu Thiết Bị"
        subtitle="Hướng camera vào mã QR Cabinet / Flycase"
        continuousModeDefault={false}
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
  searchSection: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  inputWrap: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: '#CBD5E1',
    height: 50,
  },
  input: {
    flex: 1,
    color: '#0F172A',
    fontSize: 14,
  },
  qrScanBtn: {
    width: 50,
    height: 50,
    borderRadius: 14,
    backgroundColor: '#7C3AED',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 10,
    shadowColor: '#7C3AED',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 3,
  },
  resultContainer: {
    gap: 16,
  },
  specCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 6,
  },
  specHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  specSerial: {
    color: '#0F172A',
    fontSize: 20,
    fontWeight: '800',
  },
  specLine: {
    color: '#2563EB',
    fontSize: 14,
    fontWeight: '700',
    marginTop: 2,
  },
  divider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 14,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    rowGap: 12,
  },
  gridItem: {
    width: '50%',
  },
  gridLabel: {
    color: '#64748B',
    fontSize: 12,
  },
  gridVal: {
    color: '#0F172A',
    fontSize: 14,
    fontWeight: '700',
    marginTop: 2,
  },
  historyCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 6,
  },
  repairCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    padding: 18,
    borderWidth: 1,
    borderColor: '#FCA5A5',
    shadowColor: '#DC2626',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 6,
  },
  historyHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 14,
  },
  historyTitle: {
    color: '#0F172A',
    fontSize: 15,
    fontWeight: '700',
  },
  logRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  logDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#2563EB',
    marginTop: 6,
    marginRight: 10,
  },
  logContent: {
    flex: 1,
  },
  logNote: {
    color: '#0F172A',
    fontSize: 13,
    fontWeight: '600',
  },
  logMeta: {
    color: '#64748B',
    fontSize: 11,
    marginTop: 2,
  },
  emptyText: {
    color: '#94A3B8',
    fontSize: 13,
    fontStyle: 'italic',
  },
  placeholderBox: {
    paddingVertical: 60,
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholderTitle: {
    color: '#334155',
    fontSize: 16,
    fontWeight: '700',
    marginTop: 16,
  },
  placeholderText: {
    color: '#64748B',
    fontSize: 13,
    textAlign: 'center',
    marginTop: 6,
    paddingHorizontal: 32,
    lineHeight: 18,
  },
  centerBox: {
    paddingVertical: 60,
    alignItems: 'center',
  },
  loadingText: {
    color: '#64748B',
    fontSize: 14,
    marginTop: 12,
  },
});
