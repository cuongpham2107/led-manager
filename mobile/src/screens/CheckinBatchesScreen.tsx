import { ArrowDownToLine, ArrowLeft, ChevronRight, Cog, Search, ShoppingCart, Truck } from 'lucide-react-native';
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
import { CheckinBatch, CheckinBatchTypeValue } from '../types';

interface CheckinBatchesScreenProps {
  onBack: () => void;
  onSelectBatch: (batch: CheckinBatch) => void;
}

const BatchTypeIcon: React.FC<{ type: CheckinBatchTypeValue; size?: number }> = ({ type, size = 16 }) => {
  const color = '#475569';
  if (type === 'production') return <Cog color={color} size={size} />;
  if (type === 'purchase') return <ShoppingCart color={color} size={size} />;
  return <Truck color={color} size={size} />;
};

export const CheckinBatchesScreen: React.FC<CheckinBatchesScreenProps> = ({
  onBack,
  onSelectBatch,
}) => {
  const { selectedWarehouseId } = useAuth();
  const [batches, setBatches] = useState<CheckinBatch[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);
  const [searchTerm, setSearchTerm] = useState<string>('');

  const fetchBatches = async () => {
    try {
      const response = await apiClient.get('/checkin-batches', {
        params: {
          warehouse_id: selectedWarehouseId,
          search: searchTerm.trim() || undefined,
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

  const renderItem = ({ item }: { item: CheckinBatch }) => (
    <TouchableOpacity style={styles.card} onPress={() => onSelectBatch(item)}>
      <View style={styles.cardHeader}>
        <View style={styles.codeRow}>
          <Text style={styles.batchCode}>{item.code}</Text>
          <StatusBadge label={item.batch_type.label} color={item.batch_type.color} size="sm" />
        </View>
        <ChevronRight color="#94A3B8" size={20} />
      </View>

      <View style={styles.metaRow}>
        <View style={styles.metaItem}>
          <BatchTypeIcon type={item.batch_type.value} size={14} />
          <Text style={styles.metaText}>
            {item.product_line?.name || 'LED'} — {item.device_type?.name || 'Cabinet'}
          </Text>
        </View>
      </View>

      {/* Progress Bar */}
      <View style={styles.progressSection}>
        <View style={styles.progressLabelRow}>
          <Text style={styles.progressLabel}>Tiến độ nhập kho:</Text>
          <Text style={styles.progressCount}>
            {item.scanned_count} / {item.target_items_count} ({item.progress_percent}%)
          </Text>
        </View>
        <View style={styles.progressBarBg}>
          <View style={[styles.progressBarFill, { width: `${item.progress_percent}%` }]} />
        </View>
      </View>

      <View style={styles.cardFooter}>
        <View style={styles.footerItem}>
          <StatusBadge label={item.status.label} color={item.status.color} size="sm" />
        </View>
        {item.production_note ? (
          <Text style={styles.noteText} numberOfLines={1}>
            {item.production_note}
          </Text>
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
          <Text style={styles.headerTitle}>Đợt Nhập Kho</Text>
          <Text style={styles.headerSubtitle}>Quét QR/Serial thiết bị khi nhập kho</Text>
        </View>
        <View style={styles.headerIconBox}>
          <ArrowDownToLine color="#2563EB" size={22} />
        </View>
      </View>

      {/* Search Input */}
      <View style={styles.searchWrap}>
        <Search color="#94A3B8" size={18} style={{ marginRight: 8 }} />
        <TextInput
          style={styles.searchInput}
          placeholder="Tìm theo mã IN hoặc ghi chú..."
          placeholderTextColor="#94A3B8"
          value={searchTerm}
          onChangeText={setSearchTerm}
        />
      </View>

      {isLoading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator color="#2563EB" size="large" />
          <Text style={styles.loadingText}>Đang tải danh sách đợt nhập kho...</Text>
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
              <Text style={styles.emptyTitle}>Không có đợt nhập kho nào</Text>
              <Text style={styles.emptyText}>
                Vào Filament web → Quản lý kho → Nhập kho → bấm "Tạo đợt nhập từ sản xuất" để tạo đợt mới.
              </Text>
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
  headerIconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: '#EFF6FF',
    alignItems: 'center',
    justifyContent: 'center',
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
    flexWrap: 'wrap',
  },
  batchCode: {
    color: '#2563EB',
    fontSize: 15,
    fontWeight: '800',
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  metaText: {
    color: '#475569',
    fontSize: 13,
    fontWeight: '600',
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
    backgroundColor: '#10B981',
    borderRadius: 4,
  },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  footerItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  noteText: {
    flex: 1,
    color: '#64748B',
    fontSize: 12,
    fontStyle: 'italic',
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
    lineHeight: 18,
  },
});
