import { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { Image } from 'expo-image';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { apiService, Recipe } from '@/services/api';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export default function RecipeDetailScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams<{ id: string }>();
  const colorScheme = useColorScheme();
  const colors = Colors[colorScheme ?? 'light'];

  const [recipe, setRecipe] = useState<Recipe | null>(null);
  const [loading, setLoading] = useState(true);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    loadRecipe();
  }, [id]);

  const loadRecipe = async () => {
    try {
      setLoading(true);
      const response = await apiService.getRecipe(Number(id));
      setRecipe(response.data);
    } catch (error) {
      console.error('Erro ao carregar receita:', error);
      Alert.alert('Erro', 'Não foi possível carregar a receita');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = () => {
    Alert.alert(
      'Confirmar exclusão',
      'Tem certeza que deseja excluir esta receita?',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Excluir',
          style: 'destructive',
          onPress: async () => {
            try {
              setDeleting(true);
              await apiService.deleteRecipe(Number(id));
              Alert.alert('Sucesso', 'Receita excluída com sucesso');
              router.back();
            } catch (error) {
              console.error('Erro ao excluir receita:', error);
              Alert.alert('Erro', 'Não foi possível excluir a receita');
            } finally {
              setDeleting(false);
            }
          },
        },
      ]
    );
  };

  if (loading) {
    return (
      <View style={[styles.container, styles.centerContent, { backgroundColor: colors.background }]}>
        <ActivityIndicator size="large" color={colors.tint} />
      </View>
    );
  }

  if (!recipe) {
    return (
      <View style={[styles.container, styles.centerContent, { backgroundColor: colors.background }]}>
        <Text style={[styles.errorText, { color: colors.text }]}>Receita não encontrada</Text>
      </View>
    );
  }

  return (
    <ScrollView style={[styles.container, { backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <View style={styles.headerActions}>
          <TouchableOpacity
            onPress={() => router.push(`/recipes/${id}/edit`)}
            style={styles.editButton}>
            <IconSymbol name="pencil" size={20} color={colors.tint} />
          </TouchableOpacity>
          <TouchableOpacity onPress={handleDelete} disabled={deleting}>
            <IconSymbol name="trash" size={20} color={deleting ? colors.icon : '#ff4444'} />
          </TouchableOpacity>
        </View>
      </View>

      {recipe.image && (
        <Image source={{ uri: recipe.image }} style={styles.image} contentFit="cover" />
      )}

      <View style={styles.content}>
        <Text style={[styles.title, { color: colors.text }]}>
          {recipe.name || 'Receita sem nome'}
        </Text>

        <View style={styles.metaContainer}>
          {recipe.prep_time_minutes && (
            <View style={styles.metaItem}>
              <IconSymbol name="clock" size={18} color={colors.tint} />
              <Text style={[styles.metaText, { color: colors.text }]}>
                {recipe.prep_time_minutes} minutos
              </Text>
            </View>
          )}
          {recipe.servings && (
            <View style={styles.metaItem}>
              <IconSymbol name="person.fill" size={18} color={colors.tint} />
              <Text style={[styles.metaText, { color: colors.text }]}>
                {recipe.servings} porções
              </Text>
            </View>
          )}
          {recipe.category && (
            <View style={styles.metaItem}>
              <IconSymbol name="tag.fill" size={18} color={colors.tint} />
              <Text style={[styles.metaText, { color: colors.text }]}>
                {recipe.category.name}
              </Text>
            </View>
          )}
        </View>

        {recipe.ingredients && (
          <View style={styles.section}>
            <Text style={[styles.sectionTitle, { color: colors.text }]}>Ingredientes</Text>
            <Text style={[styles.sectionContent, { color: colors.text }]}>
              {recipe.ingredients}
            </Text>
          </View>
        )}

        <View style={styles.section}>
          <Text style={[styles.sectionTitle, { color: colors.text }]}>Modo de Preparo</Text>
          <Text style={[styles.sectionContent, { color: colors.text }]}>
            {recipe.instructions}
          </Text>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  centerContent: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    paddingTop: 60,
  },
  backButton: {
    padding: 4,
  },
  headerActions: {
    flexDirection: 'row',
    gap: 16,
  },
  editButton: {
    padding: 4,
  },
  image: {
    width: '100%',
    height: 250,
  },
  content: {
    padding: 16,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    marginBottom: 16,
  },
  metaContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 16,
    marginBottom: 24,
  },
  metaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  metaText: {
    fontSize: 16,
  },
  section: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '600',
    marginBottom: 12,
  },
  sectionContent: {
    fontSize: 16,
    lineHeight: 24,
  },
  errorText: {
    fontSize: 18,
  },
});

