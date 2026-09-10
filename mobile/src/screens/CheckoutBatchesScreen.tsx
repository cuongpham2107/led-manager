import { ArrowLeft, ArrowUpRight, Calendar, ChevronRight, Layers, Search } from 'lucide-react-native';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  SafeAreaView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { StatusBadge } from '../components/StatusBadge';
import { useAuth } from '../context/AuthContext';
import { apiClient } from '../services/apiClient';
import { CheckoutBatch } from '../types';

interface CheckoutBatchesScreenProps {
  onBack: () => void;
  onSelectBatch: (batch: CheckoutBatch) => void;
}

export const CheckoutBatchesScreen: React.FC<CheckoutBatchesScreenProps> = ({
  onBack,
  onSelectBatch,
}) => {
  const { selectedWarehouseId } = useAuth();
  const [batches, setBatches] = useState<CheckoutBatch[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);
  const [searchTerm, setSearchTerm] = useState<string>('');

  const fetchBatches = async () => {
    try {
      const response = await apiClient.get('/checkout-batches', {
        params: {
          warehouse_id: selectedWarehouseId,
          search: searchTerm.trim() || undefined,
          active: 1,
        },
      });
      if (response.data?.success) {
        setBatches(response.data.data);
      }
    } catch {
      // Ignore
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    fetchBatches();
  }, [selectedWarehouseId, searchTerm]);

  const onRefresh = () => {
    setIsRefreshing(true);
    fetchBatches();
  };

  const renderItem = ({ item }: { item: CheckoutBatch }) => (
    <TouchableOpacity style={styles.card} onPress={() => onSelectBatch(item)}>
      <View style={styles.cardHeader}>
        <View style={styles.codeRow}>
          <Text style={styles.batchCode}>{item.code}</Text>
          <StatusBadge label={item.status.label} color={item.status.color} size="sm" />
        </View>
        <ChevronRight color="#94A3B8" size={20} />
      </View>

      <Text style={styles.eventName}>{item.order?.event || item.order?.order_no || 'Đơn hàng'}</Text>
      <Text style={styles.customerName}>Khách hàng: {item.customer?.name || 'N/A'}</Text>

      {/* Progress Bar */}
      <View style={styles.progressSection}>
        <View style={styles.progressLabelRow}>
          <Text style={styles.progressLabel}>Tiến độ xuất kho:</Text>
          <Text style={styles.progressCount}>
            {item.scanned_count} / {item.target_cabinets_count} Cabinets ({item.progress_percent}%)
          </Text>
        </View>
        <View style={styles.progressBarBg}>
          <View style={[styles.progressBarFill, { width: `${item.progress_percent}%` }]} />
        </View>
      </View>

      <View style={styles.cardFooter}>
        <View style={styles.footerItem}>
          <Layers color="#64748B" size={14} />
          <Text style={styles.footerText}>{item.required_area_m2} m²</Text>
        </View>
        {item.expected_return_date ? (
          <View style={styles.footerItem}>
            <Calendar color="#64748B" size={14} />
            <Text style={styles.footerText}>Trả: {item.expected_return_date}</Text>
          </View>
        ) : null}
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={onBack} style={styles.backBtn}>
          <ArrowLeft color="#0F172A" size={22} />
        </TouchableOpacity>
        <View style={styles.headerTitleWrap}>
          <Text style={styles.headerTitle}>Đợt Xuất Kho Sự Kiện</Text>
          <Text style={styles.headerSubtitle}>Danh sách phiếu xuất cần quét</Text>
        </View>
      </View>

      {/* Search Input */}
      <View style={styles.searchWrap}>
        <Search color="#94A3B8" size={18} style={{ marginRight: 8 }} />
        <TextInput
          style={styles.searchInput}
          placeholder="Tìm theo mã OUT, số đơn hoặc khách hàng..."
          placeholderTextColor="#94A3B8"
          value={searchTerm}
          onChangeText={setSearchTerm}
        />
      </View>

      {isLoading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator color="#2563EB" size="large" />
          <Text style={styles.loadingText}>Đang tải danh sách đợt xuất kho...</Text>
        </View>
      ) : (
        <FlatList
          data={batches}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor="#2563EB" />}
          ListEmptyComponent={
            <View style={styles.emptyBox}>
              <Text style={styles.emptyTitle}>Không có đợt xuất kho nào</Text>
              <Text style={styles.emptyText}>Hiện chưa có phiếu xuất kho nào phù hợp với bộ lọc.</Text>
            </View>
          }
        />
      )}
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
  searchWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FFFFFF',
    margin: 16,
    paddingHorizontal: 14,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#CBD5E1',
    height: 46,
  },
  searchInput: {
    flex: 1,
    color: '#0F172A',
    fontSize: 14,
  },
  listContent: {
    paddingHorizontal: 16,
    paddingBottom: 24,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#64748B',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 6,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  codeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  batchCode: {
    color: '#2563EB',
    fontSize: 15,
    fontWeight: '800',
  },
  eventName: {
    color: '#0F172A',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 4,
  },
  customerName: {
    color: '#64748B',
    fontSize: 13,
    marginBottom: 12,
  },
  progressSection: {
    marginBottom: 12,
  },
  progressLabelRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  progressLabel: {
    color: '#64748B',
    fontSize: 12,
  },
  progressCount: {
    color: '#0F172A',
    fontSize: 12,
    fontWeight: '700',
  },
  progressBarBg: {
    height: 8,
    backgroundColor: '#F1F5F9',
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    backgroundColor: '#2563EB',
    borderRadius: 4,
  },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  footerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  footerText: {
    color: '#64748B',
    fontSize: 12,
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
  emptyBox: {
    padding: 32,
    alignItems: 'center',
  },
  emptyTitle: {
    color: '#334155',
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 6,
  },
  emptyText: {
    color: '#94A3B8',
    fontSize: 13,
    textAlign: 'center',
  },
});
