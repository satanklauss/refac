import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"

export function RecentSales() {
  return (
    <div className="space-y-8">
      <div className="flex items-center">
        <Avatar className="h-9 w-9">
          <AvatarFallback>UR</AvatarFallback>
        </Avatar>
        <div className="ml-4 space-y-1">
          <p className="text-sm font-medium leading-none">Uri</p>
          <p className="text-sm text-muted-foreground">
            hotwheelscollector@gmail.gr
          </p>
        </div>
        <div className="ml-auto font-medium">+$1,999.00</div>
      </div>
      <div className="flex items-center">
        <Avatar className="flex h-9 w-9 items-center justify-center space-y-0 border">
          <AvatarFallback>DD</AvatarFallback>
        </Avatar>
        <div className="ml-4 space-y-1">
          <p className="text-sm font-medium leading-none">Davey</p>
          <p className="text-sm text-muted-foreground">assclapper@live.co.uk</p>
        </div>
        <div className="ml-auto font-medium">+$39.00</div>
      </div>
      <div className="flex items-center">
        <Avatar className="h-9 w-9">
          <AvatarFallback>MM</AvatarFallback>
        </Avatar>
        <div className="ml-4 space-y-1">
          <p className="text-sm font-medium leading-none">Matteos</p>
          <p className="text-sm text-muted-foreground">
            ceofordmondeo@prodon.me
          </p>
        </div>
        <div className="ml-auto font-medium">+$299.00</div>
      </div>
      <div className="flex items-center">
        <Avatar className="h-9 w-9">
        <AvatarImage
              src="data:image/png;base64,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"
              />
          <AvatarFallback>SU</AvatarFallback>
        </Avatar>
        <div className="ml-4 space-y-1">
          <p className="text-sm font-medium leading-none">Stef</p>
          <p className="text-sm text-muted-foreground">sunny@slotangels.es</p>
        </div>
        <div className="ml-auto font-medium">+$99.00</div>
      </div>
      <div className="flex items-center">
        <Avatar className="h-9 w-9">
          <AvatarFallback>VA</AvatarFallback>
        </Avatar>
        <div className="ml-4 space-y-1">
          <p className="text-sm font-medium leading-none">Valerie</p>
          <p className="text-sm text-muted-foreground">bighandgirl.asl@yahoo.com</p>
        </div>
        <div className="ml-auto font-medium">+$39.00</div>
      </div>
    </div>
  )
}
