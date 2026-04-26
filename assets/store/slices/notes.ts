import { createSlice, createAsyncThunk, PayloadAction } from '@reduxjs/toolkit';
import { NoteResponseDto } from '../../types/notes';
import { api } from '../../api';

const PAGE_SIZE = 10;

interface NotesState {
  notes: NoteResponseDto[];
  offset: number;
  hasMore: boolean;
  loading: boolean;
  loadingMore: boolean;
  error: string | null;
  activeSearch: string | null;
  initialized: boolean;
  lastSearchQuery: string | null;
  scrollPosition: number;
}

const initialState: NotesState = {
  notes: [],
  offset: 0,
  hasMore: true,
  loading: false,
  loadingMore: false,
  error: null,
  activeSearch: null,
  initialized: false,
  lastSearchQuery: null,
  scrollPosition: 0,
};

export const loadNotes = createAsyncThunk(
  'notes/loadNotes',
  async (search: string | null) => {
    const data = search
      ? await api.notes.search(search, PAGE_SIZE, 0)
      : await api.notes.list(PAGE_SIZE, 0);
    return {
      notes: data.data,
      hasMore: PAGE_SIZE < data.pagination.total,
    };
  }
);

export const loadMoreNotes = createAsyncThunk(
  'notes/loadMoreNotes',
  async (
    { search, offset }: { search: string | null; offset: number },
    { rejectWithValue }
  ) => {
    try {
      const data = search
        ? await api.notes.search(search, PAGE_SIZE, offset)
        : await api.notes.list(PAGE_SIZE, offset);
      return {
        notes: data.data,
        offset: offset + PAGE_SIZE,
        hasMore: offset + PAGE_SIZE < data.pagination.total,
      };
    } catch (error) {
      return rejectWithValue(
        error instanceof Error ? error.message : 'Failed to load more notes'
      );
    }
  }
);

const notesSlice = createSlice({
  name: 'notes',
  initialState,
  reducers: {
    setActiveSearch(state, action: PayloadAction<string | null>) {
      state.activeSearch = action.payload;
    },
    setScrollPosition(state, action: PayloadAction<number>) {
      state.scrollPosition = action.payload;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(loadNotes.pending, (state) => {
        state.loading = true;
        state.error = null;
        state.offset = 0;
      })
      .addCase(loadNotes.fulfilled, (state, action) => {
        state.notes = action.payload.notes;
        state.hasMore = action.payload.hasMore;
        state.offset = PAGE_SIZE;
        state.loading = false;
        state.initialized = true;
        state.lastSearchQuery = state.activeSearch;
      })
      .addCase(loadNotes.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message || 'Failed to load notes';
      })
      .addCase(loadMoreNotes.pending, (state) => {
        state.loadingMore = true;
      })
      .addCase(loadMoreNotes.fulfilled, (state, action) => {
        state.notes = [...state.notes, ...action.payload.notes];
        state.offset = action.payload.offset;
        state.hasMore = action.payload.hasMore;
        state.loadingMore = false;
      })
      .addCase(loadMoreNotes.rejected, (state, action) => {
        state.loadingMore = false;
        state.error = action.payload as string;
      });
  },
});

export const { setActiveSearch, setScrollPosition } = notesSlice.actions;
export default notesSlice.reducer;
