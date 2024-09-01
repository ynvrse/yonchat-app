<?php

use App\Models\User;
use App\Models\Message;
use App\Events\ChatEvent;
use Illuminate\Support\Facades\Log;


use function Livewire\Volt\{state,mount,computed,rules,on};

state(
    receiver: '',
    receiverName: '',
    receiverEmail: '',
    sender: '',
    message: '',
    status: 'offline',
    isTyping: false,
    profilePicture: '',
);

on(['echo:sending,ChatEvent' => function ($eventData) {
    $this->dispatch('scrollBottom');
    if ($eventData['senderId'] === $this->receiver && $eventData['receiverId'] === $this->sender) {
        $this->isTyping = ($eventData['status'] === 'typing');
        $this->status = $eventData['status'];
    }
}]);


mount(function() {
    $this->sender = auth()->id();
    $emailReceiver = request('receiver');

    $this->profilePicture = auth()->user()->photo;
    if($emailReceiver){
        $user = User::where('email', $emailReceiver)->firstOr(function() {
            abort(404, 'User not found');
        });

        if ($user) {
            $this->receiverEmail = $user->email;
            $this->receiver = $user->id;
            $this->receiverName = $user->name;
        }
    }
});




rules([
    'sender' => 'required',
    'receiver' => 'nullable',
    'message' => 'required|max:200',
]);

$sendMessage = function() {

    try {
        try {
            event(new ChatEvent($this->sender, $this->receiver, 'online'));
        } catch (Exception $th) {
          
            echo("Event tidak dapat dikirim: " . $th->getMessage());
          
        }

        $message = Message::create($this->validate());
        
        $this->dispatch('scrollBottom');
        $this->message = '';
    } catch (Exception $e) {
        echo "Gagal Mengirim Pesan : " . $e->getMessage();
    }
};

$delete = function() {
    try {
        DB::transaction(function() {
            Message::where(function($query) {
                $query->where(function($q) {
                    $q->where('sender', $this->sender)->where('receiver', $this->receiver);
                })->orWhere(function($q) {
                    $q->where('sender', $this->receiver)->where('receiver', $this->sender);
                });
            })->delete();
            
            event(new ChatEvent($this->sender, $this->receiver, 'online'));
        });
    } catch (Exception $e) {
        Log::error("Gagal menghapus pesan", ['error' => $e->getMessage()]);
    }
};

$loadReceivers = computed(function(){
    $currentUserEmail = auth()->user()->email;
    return User::where('email', '!=', $currentUserEmail)->latest()->get();
});

$loadMessages = computed(function() {
    return Message::where(function($query) {
        $query->where(function($q) {
            $q->where('sender', auth()->id())->where('receiver', $this->receiver);
        })->orWhere(function($q) {
            $q->where('sender', $this->receiver)->where('receiver', auth()->id());
        });
    })->get();
});


$selectReceiver = function(){
    $this->redirect('/chat/' . $this->receiverEmail, navigate: true);
};



$startTyping = function() {
    event(new ChatEvent($this->sender, $this->receiver, 'typing'));
};

$stopTyping = function() {
    event(new ChatEvent($this->sender, $this->receiver, 'online'));
};

?>

<x-app-layout>

    @volt
    <div>

        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center bg-white">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        yonchat-app
                    </h2>
                    <x-button wire:click="delete" type="button" variant="outline">
                        <x-lucide-flame class="mr-2 size-4" /> Bersihkan Riwayat
                    </x-button>
                </div>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <x-card>
                        <x-card.header>

                            <x-card.title>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <img class="w-12 h-12 rounded-full"
                                            src="{{Storage::url($this->profilePicture)}}" alt="pp">
                                        <x-select wire:model="receiverEmail" wire:change="selectReceiver"
                                            class="w-[180px]">
                                            <option value="" disabled>Kirim Pesan Ke</option>
                                            @forelse ($this->loadReceivers as $item)
                                            <option value="{{ $item->email }}">
                                                {{ $item->name }}
                                            </option>
                                            @empty
                                            <option value="" disabled>User Tidak Ditemukan</option>
                                            @endforelse
                                        </x-select>
                                    </div>

                                    <span class="text-sm text-gray-500">
                                        @if($this->isTyping)
                                        Sedang mengetik...
                                        @else
                                        {{ $this->status }}
                                        @endif
                                    </span>
                                </div>



                            </x-card.title>

                        </x-card.header>
                        <x-card.content>

                            <div id="chat-messages" class="h-80 overflow-y-auto p-4 space-y-4 border-y">

                                @if ($this->receiver)

                                @foreach ($this->loadMessages as $item)

                                @php
                                $createdAt = $item->created_at;
                                $isJustNow = $createdAt->diffInMinutes(now()) < 1; $isMoreThanAnHour=$createdAt->
                                    diffInHours(now()) >= 1;
                                    @endphp

                                    @if ($item->sender === auth()->id())
                                    <!-- Pesan pengirim -->
                                    <div class="flex items-start justify-end gap-2.5 mb-2">
                                        <div
                                            class="flex flex-col w-full max-w-[320px] leading-1.5 p-4 border-gray-200 bg-black text-white rounded-s-xl rounded-se-xl dark:bg-gray-700 ml-auto">
                                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                                <span class="text-sm font-semibold">
                                                    Anda
                                                </span>

                                                <span class="text-sm font-normal text-gray-200">
                                                    @if($isJustNow)
                                                    Baru saja
                                                    @elseif($isMoreThanAnHour)
                                                    {{ $createdAt->format('H:i') }}
                                                    @else
                                                    {{ $createdAt->diffForHumans() }}
                                                    @endif
                                                </span>

                                            </div>
                                            <p class="text-sm font-normal py-2.5">
                                                {{$item->message}}
                                            </p>
                                        </div>
                                    </div>

                                    @else
                                    <!-- Pesan penerima -->
                                    <div class="flex items-start gap-2.5">

                                        <div
                                            class="flex flex-col w-full max-w-[320px] leading-1.5 p-4 border-gray-200 bg-gray-200 rounded-e-xl rounded-es-xl dark:bg-gray-700">
                                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                                <span class="text-sm font-semibold text-gray-900 ">

                                                    {{$this->receiverName}}

                                                </span>

                                                <span class="text-sm font-normal text-gray-500">
                                                    @if($isJustNow)
                                                    Baru saja
                                                    @elseif($isMoreThanAnHour)
                                                    {{ $createdAt->format('H:i') }}
                                                    @else
                                                    {{ $createdAt->diffForHumans() }}
                                                    @endif
                                                </span>

                                            </div>
                                            <p class="text-sm font-normal py-2.5 text-gray-900 ">
                                                {{$item->message}}
                                            </p>

                                        </div>

                                    </div>


                                    @endif
                                    @endforeach
                                    <div class="relative">
                                        <div wire:loading wire:target="sendMessage"
                                            class="absolute inset-0 flex items-start justify-end gap-2.5 mb-2">
                                            <div
                                                class="flex flex-col w-full max-w-[320px] leading-1.5 p-4 border-gray-200 bg-gray-800 text-white rounded-s-xl rounded-se-xl dark:bg-gray-700 ml-auto">
                                                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                                    <span class="text-sm font-semibold">Anda</span>
                                                    <span class="text-sm font-normal text-gray-200">Mengirim
                                                        Pesan</span>
                                                </div>
                                                <p class="text-sm font-normal py-2.5">
                                                    {{$this->message}}
                                                </p>
                                            </div>
                                        </div>
                                    </div>



                                    @if($this->isTyping)

                                    <div
                                        class="flex flex-col w-full max-w-[100px] leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700">
                                        <livewire:typing />
                                    </div>
                                    @endif
                                    @else
                                    @endif





                            </div>
                        </x-card.content>
                        <form wire:submit="sendMessage">
                            <x-card.footer>
                                <div class="flex w-full  items-center space-x-2">
                                    {{--
                                    <x-input wire:model="message" type="text" placeholder="Ketik Pesan.." /> --}}
                                    <x-input required type="text" wire:model.live="message" wire:keydown="startTyping"
                                        wire:keyup.debounce.1000ms="stopTyping" />
                                    <x-button type="submit">
                                        <x-lucide-send class="mr-2 size-4" /> Kirim
                                    </x-button>
                                </div>

                            </x-card.footer>
                        </form>

                    </x-card>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function scrollToBottom() {
                    const chatMessages = document.getElementById('chat-messages');
                    if (chatMessages) {
                        setTimeout(() => {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }, 100);
                    }
                }
            
                scrollToBottom();

                if (window.Livewire) {
                    window.Livewire.on('scrollBottom', scrollToBottom);
                    document.addEventListener('livewire:navigated', scrollToBottom);
                }
            });

        </script>


    </div>
    @endvolt
</x-app-layout>